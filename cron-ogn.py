#!/bin/env python3
import urllib.request
import urllib
from xml.dom import minidom, Node
import sys
import re
import datetime
import errno
import socket

#  https://github.com/glidernet/ogn-live

try:
	request = urllib.request.urlopen("http://live.glidernet.org/lxml.php?a=1&b=51.0&c=50.0&d=6.5&e=5.5&y=15")
	replyString = request.read()
	xmlDoc = minidom.parseString(replyString)
except urllib.error.HTTPError as err:
	print("Cannot get XML, error: ", err)
	sys.exit("Cannot get XML, error: ", err)
except:
	print('Not found or invalid XML')
	sys.exit("Cannot get or parse XML, error: ", err)

# <markers>
#<m a="50.818668,6.230770,EB,D-KEEB,713,13:14:56,7140,261,76,-4.6,1,AIRS89035,3E66E9,aebcb1ba"/>
#<m a="50.822979,6.191550,_45,5be20545,193,07:53:33,26423,0,0,0.1,8,EDKA,0,5be20545"/>
#</markers>

prog = re.compile(r'(.+),(.+),(.+),(.+),(.+),(..:..:..),(.+?),(.+),(.+),(.+),.*')
today =  datetime.datetime.now(datetime.timezone.utc).strftime('%Y-%m-%d')

for marker in xmlDoc.getElementsByTagName('m'):
	aValue = marker.getAttribute('a')
	print(aValue)
	m = prog.match(aValue)
	latitude = m.group(1)
	longitude = m.group(2)
	tailNumber = m.group(4).replace('-', '')
	altitude = float(m.group(5)) * 3.28084
	timeUTC = m.group(6)
	timeSinceMeasure = int(m.group(7))
	velocity = -1

	print('lat/lon', latitude, longitude)
	print('ID', tailNumber)

	if timeSinceMeasure > 30 * 60:
		print("Skipping " + tailNumber + " as it is too old " + timeUTC)
		continue
	# Let's insert the glider data in the DB
	try:
		response = urllib.request.urlopen("https://www.spa-aviation.be/resa/add_to_tracks.php?local=yes&icao24=" + tailNumber + '&tail_number=' + tailNumber + '&daytime=' + urllib.parse.quote(today + ' ' + timeUTC) + '&longitude=' + longitude + '&latitude=' + latitude + '&altitude=' + str(altitude) + '&velocity=' + str(velocity) + '&sensor=0&source=OGN')
		print('HTTP status', response.status)
		print('HTTP reason', response.reason)
		print('HTTP response', response.read())
	except urllib.error.URLError as e:
		print('Error when connecting to RAPCS: ' + str(e))
	except SocketError as e:
		print('Error in connecting to RPACS web service: ' + str(e))
sys.exit()

aircrafts = response['aircraft']

for aircraft in aircrafts:
	aircraft['hex'] = aircraft['hex'].lower()  # just in case...
	if not 'alt_baro' in aircraft: aircraft['alt_baro'] = -1
	if not 'gs' in aircraft: aircraft['gs'] = -1
	if not 'squawk' in aircraft: aircraft['squawk'] = '----'
	if not 'flight' in aircraft: aircraft['flight'] = '-'
	if aircraft['hex'] in my_icao:
		print('Found my aircraft at ' + now)
		print(aircraft)
		if 'lon' in aircraft:
			try:
				response = urllib.request.urlopen("https://www.spa-aviation.be/resa/add_to_tracks.php?icao24=" + aircraft['hex'] + '&daytime=' + urllib.parse.quote(now) + '&longitude=' + str(aircraft['lon']) + '&latitude=' + str(aircraft['lat']) + '&altitude=' + str(aircraft['alt_baro']) + '&velocity=' + str(aircraft['gs']) + '&squawk=' + urllib.parse.quote(aircraft['squawk']) + '&sensor=0&source=FA-evyncke')
			except urllib.error.URLError as e:
				print('Error when connecting to RAPCS: ' + str(e))
			except SocketError as e:
				print('Error in connecting to RPACS web service: ' + str(e))
		else:
			print('Alas no location...')
	elif (aircraft['alt_baro'] > 0 and aircraft['alt_baro'] <= 5000): # Not my aircraft but it is a local flight it seems
		print('Found a nearby aircraft at ' + now)
		print(aircraft)
		if 'lon' in aircraft:
			try:
				response = urllib.request.urlopen("https://www.spa-aviation.be/resa/add_to_tracks.php?local=yes&icao24=" + aircraft['hex'] + '&daytime=' + urllib.parse.quote(now) + '&longitude=' + str(aircraft['lon']) + '&latitude=' + str(aircraft['lat']) + '&altitude=' + str(aircraft['alt_baro']) + '&velocity=' + str(aircraft['gs']) + '&tail_number=' + urllib.parse.quote(aircraft['flight']) + '&source=FA-evyncke')
			except urllib.error.URLError as e:
				print('Error when connecting to RAPCS: ' + str(e))
			except SocketError as e:
				print('Error in connecting to RPACS web service: ' + str(e))
		else:
			print('Alas no location...')
