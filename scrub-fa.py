#!/usr/bin/env python3

"""
   Copyright 2021 Eric Vyncke

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.
"""

import urllib.request
import urllib
import json
import re
import datetime
import time


planes = {	# Dictionnary for all the planes with their icao 24-bit identifier
	'OOALD': '448584',
	'OOALE': '448585',
	'OOFMX': '4499b8',
	'OOJRB': '44aa42',
	'PHAML': '484b0c'}

preamble = '<script>var trackpollBootstrap = '
postamble = ';</script>'

headers = {
	'User-Agent': 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/88.0.4324.182 Safari/537.36',
	'Cache-Control': 'max-age=0',
	'Upgrade-Insecure-Requests': '1',
	'Accept': 'text/html,application/xhtml+xml,application/xml'
}

# Let's do it now !

# TODO handle cookies with https://docs.python.org/3/library/http.cookiejar.html

one_hour_back = time.time() - 3600

for plane, icao24 in planes.items():
	print('plane: ' + plane + ', icao24: ' + icao24)
	request = urllib.request.Request("https://flightaware.com/live/flight/" + plane, data = None, headers = headers, method = 'GET')
	reply = urllib.request.urlopen(request)
	# Let's skip until past the preamble and before the postamble, using '?' to avoid being greedy
	match = re.search(preamble + '(.+?)' + postamble, str(reply.read()))
	json_string = match.group(1)
	json_dict = json.loads(json_string) 
	if not 'flights' in json_dict:
		print('No flights element found')
		continue
	for flight in json_dict['flights']:
		print('Found flight: ' + flight)
		if not 'track' in json_dict['flights'][flight]:
			print('No track element found')
			continue
		for p in json_dict['flights'][flight]['track']:
			if p['timestamp'] <= one_hour_back:
				print('Skipping old entry...')
				continue
			if 'alt' in p:
				alt = p['alt'] * 100
			else:
				alot = -1
			if not 'gs' in p:
				p['gs'] = -1
			if not 'coord' in p:
				lat = -1
				lon = -1
			else:
				lon = p['coord'][0]
				lat = p['coord'][1]
			timestamp = datetime.datetime.fromtimestamp(p['timestamp']).strftime('%Y-%m-%d %H:%M:%S')
			print("https://www.spa-aviation.be/resa/add_to_tracks.php?icao24=" + icao24 + '&daytime=' + urllib.parse.quote(timestamp) + '&longitude=' + str(lon) + '&latitude=' + str(lat) + '&altitude=' + str(alt) + '&velocity=' + str(p['gs']) + '&squawk=----&sensor=0&source=FlightAware')
			response = urllib.request.urlopen("https://www.spa-aviation.be/resa/add_to_tracks.php?icao24=" + icao24 + '&daytime=' + urllib.parse.quote(timestamp) + '&longitude=' + str(lon) + '&latitude=' + str(lat) + '&altitude=' + str(alt) + '&velocity=' + str(p['gs']) + '&squawk=----&sensor=0&source=FlightAware')
