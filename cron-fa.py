#!/bin/env python3
import urllib.request
import urllib
import json
import datetime
import errno
import socket

# https://github.com/flightaware/dump1090/blob/master/README-json.md

my_icao = [ '448585', '4499b8', '44aa42', '448584', '484b0c', '44ce10']
my_icao.append('44ce01') # OO-SPA
my_icao.append('44ccb8') # OO-SEX
my_icao.append('449a89') # OO-FTI
my_fa_site_fqdn = 'raspeberry.local'
my_fa_site_fqdn = 'localhost' 
my_fa_site_port = 8080 

request = urllib.request.urlopen("http://" + my_fa_site_fqdn + ":" + str(my_fa_site_port) + '/data/aircraft.json')

response = json.loads(request.read())
now = datetime.datetime.fromtimestamp(response['now'], tz=datetime.timezone.utc).strftime('%Y-%m-%d %H:%M:%S') # It is now in local time while it should be in UTC
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
