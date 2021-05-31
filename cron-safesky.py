#!/bin/env python3
import urllib.request
import urllib
import sys
import re
import datetime
import errno
import socket
import json
import time

try:
	with open ("safesky.key", "r") as myfile:
		apiKey = myfile.read().replace('\n','')
except:
	print('Cannot read the API key')
	sys.exit("Cannot read the SafeSky key, error: " + str(sys.exc_info()[0]))

try:
	request = urllib.request.Request("https://public-api.safesky.app/v1/beacons/?viewport=49.5,5.0,51.5,7.0", method='GET')

	request.add_header('User-Agent', 'RAPCS Spa Aviation - contact eric@vyncke.org')
	request.add_header('x-api-key', apiKey)
	reply = urllib.request.urlopen(request)
	replyString = reply.read().decode()
except urllib.error.HTTPError as e:
	print('Error when connecting to SafeSky: ' + str(e))
except:
	print('Cannot access the SafeSky API')
	sys.exit("Cannot access the SafeSky, error: " + str(sys.exc_info()[0]))

try:
	response = json.loads(replyString)
except json.decoder.JSONDecodeError as e:
	print('Cannot decode the JSON: ' + str(e))
except:
	sys.exit("Cannot decode the JSON, error: " + str(sys.exc_info()[0]))

now = int(time.time())
for beacon in response:
	# {'source': 'safesky', 'status': 'AIRBORNE', 'ground_speed': 30, 'course': 294, 'latitude': 44.40415, 'longitude': 6.49135, 'transponder_type': 'ADS-BI', 'call_sign': 'FJVDL', 'last_update': 1622020787, 'altitude': 1986, 'vertical_rate': 0, 'accuracy': 5, 'altitude_accuracy': 3, 'baro_altitude': -1, 'beacon_type': 'GYROCOPTER', 'mlat': False, 'id': 'kp2aojft'}
	print(beacon)
	if now - beacon['last_update'] > 30 * 60:
		print("Skipping " + beacon['call_sign'] + " as it is too old " + beacon['last_update'])
		continue
	latitude = beacon['latitude']
	longitude = beacon['longitude']
	track = beacon['course']
	altitude = round(beacon['altitude'] * 3.28084) # meter to feet conversion
	velocity = round(beacon['ground_speed']  * 1.94384) # m/sec to knots conversion
	daytime = datetime.datetime.fromtimestamp(beacon['last_update'], datetime.timezone.utc).strftime('%Y-%m-%d %H:%M:%S')
	print('timestamp=', beacon['last_update'], ', date', daytime)
	if 'call_sign' in beacon:
		tailNumber = beacon['call_sign']
	else:
		if 'id' in beacon:
			tailNumber = beacon['id']
		else:
			print('Skipping as no valid call_sign/id')
			continue
	tailNumber = urllib.parse.quote(tailNumber[0:6])

	# Let's insert the glider data in the DB
	try:
		response = urllib.request.urlopen("https://www.spa-aviation.be/resa/add_to_tracks.php?local=yes&icao24=" + tailNumber + '&tail_number=' + tailNumber + '&daytime=' + urllib.parse.quote(daytime) + '&longitude=' + str(longitude) + '&latitude=' + str(latitude) + '&altitude=' + str(altitude) + '&velocity=' + str(velocity) + '&track=' + str(track) + '&sensor=0&source=SafeSky')
		if response.status != 200:
			print('HTTP status', response.status)
			print('HTTP reason', response.reason)
			print('HTTP response', response.read())
	except (urllib.error.HTTPError, urllib.error.URLError) as e:
		print('Error when connecting to RAPCS: ' + str(e))
	except Exception as e:
		print('Error in connecting to RAPCS web service: ' + str(e))

sys.exit()
