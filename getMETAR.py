#!/usr/bin/env python3
import argparse
import datetime
import os
import re
import time
from pathlib import Path
from urllib.parse import urljoin, urlparse, parse_qs

import requests

BASE_URL = "https://ops.skeyes.be"
#COOKIE_PATH = Path("/var/www/resa/belgocontrol.cookie")
COOKIE_PATH = Path("/tmp/belgocontrol.cookie")
#JSESSIONID_PATH = Path("/var/www/resa/belgocontrol.jsessionid")
JSESSIONID_PATH = Path("/tmp/belgocontrol.jsessionid")
OUTPUT_DIR = Path("/var/www/nav")
USER_AGENT = (
    "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) "
    "AppleWebKit/537.36 (KHTML, like Gecko) "
    "Chrome/136.0.0.0 Safari/537.36"
)

DEFAULT_STATIONS = ["EBSP", "EBSH", "EBFN", "EBFS", "EBBE", "EBBL"]
DEFAULT_SLEEP = 10


def parse_cookie_header(cookie_header: str) -> requests.cookies.RequestsCookieJar:
    jar = requests.cookies.RequestsCookieJar()
    for part in cookie_header.split(";"):
        if "=" not in part:
            continue
        name, value = part.strip().split("=", 1)
        jar.set(name, value, domain="ops.skeyes.be", path="/")
    return jar


def restore_cookie(session: requests.Session, cookie_path: Path, jsessionid_path: Path):
    cookie_string = ""
    jsessionid = ""
    if cookie_path.exists():
        cookie_string = cookie_path.read_text().strip()
        if cookie_string:
            session.cookies.update(parse_cookie_header(cookie_string))
            print(f"Cookie restored: {cookie_string}")

    if jsessionid_path.exists():
        jsessionid = jsessionid_path.read_text().strip()
        if jsessionid:
            print(f"JSESSIONID restored: {jsessionid}")

    return cookie_string, jsessionid


def save_cookie(session: requests.Session, cookie_path: Path, jsessionid_path: Path, jsessionid: str):
    cookie_parts = []
    for cookie in session.cookies:
        cookie_parts.append(f"{cookie.name}={cookie.value}")
    cookie_string = "; ".join(cookie_parts)
    if cookie_string:
        cookie_path.write_text(cookie_string)
        print(f"Cookie saved: {cookie_string}")
    if jsessionid:
        jsessionid_path.write_text(jsessionid)
        print(f"JSESSIONID saved: {jsessionid}")


def pick_login_fields(html: str) -> tuple[str, str]:
    action = ""
    localip = ""

    action_match = re.search(
        r"<form[^>]+name=[\"']loginForm[\"'][^>]+action=[\"']([^\"']+)[\"']",
        html,
        re.I,
    )
    if action_match:
        action = action_match.group(1)

    localip_match = re.search(
        r"<input[^>]+name=[\"']localip[\"'][^>]+value=[\"']([^\"']+)[\"']",
        html,
        re.I,
    )
    if localip_match:
        localip = localip_match.group(1)

    return action, localip


def parse_jsessionid_from_location(location: str) -> str:
    match = re.search(r";jsessionid=([^.\?&]+)", location)
    if match:
        return f";jsessionid={match.group(1)}"
    return ""


def http_login(
    session: requests.Session,
    redirect_url: str,
    username: str,
    password: str,
    airport: str,
    jsessionid: str,
) -> tuple[requests.Response, str]:
    print("===============\nStart of http_login()")
    print(f"redirect_url={redirect_url}")

    location_jsessionid = parse_jsessionid_from_location(redirect_url)
    if location_jsessionid:
        jsessionid = location_jsessionid
        print(f"Found jsessionid: {jsessionid}")

    response = session.get(redirect_url, headers={"User-Agent": USER_AGENT}, allow_redirects=True)
    response.raise_for_status()

    action, localip = pick_login_fields(response.text)
    if not action:
        raise RuntimeError("Unable to locate login form action in the HTML response.")
    if not localip:
        raise RuntimeError("Unable to locate hidden localip field in the login page.")

    login_url = urljoin(BASE_URL, action)
    print(f"Found login action: {login_url}")
    print(f"Found localip: {localip}")

    post_data = {
        "localip": localip,
        "username": username,
        "password": password,
        "icaocodes": airport,
        "templateName": "",
        "selectContry": "",
        "template": "EBSP",
        "newTemplateName": "",
        "cmd": "retrieveOpmet",
        "url": "operInfoMeteoInfo.do?cmd=retrieveOpmet",
        "metar": "on",
        "metarHistory": "0",
    }

    response = session.post(
        login_url,
        data=post_data,
        headers={"User-Agent": USER_AGENT},
        allow_redirects=False,
    )

    if response.status_code in (301, 302):
        next_location = response.headers.get("Location")
        if next_location:
            next_url = urljoin(BASE_URL, next_location)
            print(f"Login redirected to {next_url}")
            post_data = {
                "localip": localip,
                "icaocode": airport,
                "cmd": "retrieveOpmet",
                "url": "operInfoMeteoInfo.do",
                "metar": "on",
                "metarHistory": "0",
            }
            response = session.post(
                next_url,
                data=post_data,
                headers={"User-Agent": USER_AGENT},
                allow_redirects=True,
            )
    return response, jsessionid


def request_metar(
    session: requests.Session,
    station: str,
    jsessionid: str,
) -> requests.Response:
    url = f"{BASE_URL}/opersite/opmetData.do{jsessionid}?cmd=retrieveOpmet"
    print(f"Requesting METAR for {station} using {url}")
    response = session.post(
        url,
        data={
            "templateName": "",
            "newTemplateName": "",
            "selectCountry": "",
            "template": "EBSP",
            "icaocodes": station,
            "metar": "on",
            "metarHistory": "0",
        },
        headers={"User-Agent": USER_AGENT},
        allow_redirects=False,
        timeout=20,
    )
    print(f"HTTP {response.status_code} returned")
    print("Response headers:")
    print(dict(response.headers))
    return response


def extract_metar_text(html: str) -> str:
    match = re.search(r"(?:METAR|SPECI)\s+[A-Z]{3,4}\s+\d{6}Z", html, re.I)
    if not match:
        return "NOT AVAILABLE"

    metar = html[match.start():]
    metar = re.split(r"<", metar, 1)[0]
    metar = re.sub(r"\s+", " ", metar).strip()
    return metar


def write_metar_file(station: str, metar_text: str, output_dir: Path):
    output_dir.mkdir(parents=True, exist_ok=True)
    filename = output_dir / f"{station}.TXT"
    utc_now = datetime.datetime.now(datetime.UTC).strftime("%Y/%m/%d %H:%M")
    filename.write_text(f"{utc_now}\n{metar_text}\n")
    print(f"Saved METAR to {filename}")


def get_station_metar(
    session: requests.Session,
    station: str,
    username: str,
    password: str,
    output_dir: Path,
    cookie_path: Path,
    jsessionid_path: Path,
    jsessionid: str,
) -> str:
    response = request_metar(session, station, jsessionid)
    if response.status_code in (301, 302):
        location = response.headers.get("Location")
        if not location:
            raise RuntimeError("Redirect response did not include Location header.")
        print("Login is required, following redirect.")
        response, jsessionid = http_login(session, urljoin(BASE_URL, location), username, password, station, jsessionid)
        save_cookie(session, cookie_path, jsessionid_path, jsessionid)

    if response.status_code not in (200,):
        raise RuntimeError(f"Unexpected HTTP status code {response.status_code} for {station}")

    metar_text = extract_metar_text(response.text)
    write_metar_file(station, metar_text, output_dir)
    return jsessionid


def parse_args():
    parser = argparse.ArgumentParser(description="Fetch METAR reports from ops.skeyes.be")
    parser.add_argument("stations", nargs="*", default=DEFAULT_STATIONS)
    parser.add_argument("--sleep", type=int, default=DEFAULT_SLEEP, help="Seconds to sleep between requests")
    parser.add_argument("--output-dir", type=Path, default=OUTPUT_DIR, help="Directory to save METAR text files")
    parser.add_argument("--cookie-file", type=Path, default=COOKIE_PATH, help="Cookie storage file")
    parser.add_argument("--jsessionid-file", type=Path, default=JSESSIONID_PATH, help="JSESSIONID storage file")
    parser.add_argument("--username", default=os.getenv("METAR_USERNAME", "ebspcrew"))
    parser.add_argument("--password", default=os.getenv("METAR_PASSWORD", "EBSPcrew124640"))
    return parser.parse_args()


def main():
    args = parse_args()
    session = requests.Session()
    session.headers.update({"User-Agent": USER_AGENT})
    session.cookies.update(
        {
            "COOKIE_SUPPORT": "true",
            "accepted_cookie": "true",
            "GUEST_LANGUAGE_ID": "en_GB",
        }
    )
    cookie_string, jsessionid = restore_cookie(session, args.cookie_file, args.jsessionid_file)

    for index, station in enumerate(args.stations, start=1):
        jsessionid = get_station_metar(
            session,
            station,
            args.username,
            args.password,
            args.output_dir,
            args.cookie_file,
            args.jsessionid_file,
            jsessionid,
        )
        if index < len(args.stations):
            sleep_seconds = args.sleep
            print(f"Sleeping {sleep_seconds} seconds before next station")
            time.sleep(sleep_seconds)


if __name__ == "__main__":
    main()
