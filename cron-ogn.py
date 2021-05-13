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
# https://github.com/glidernet/ogn-live#backend
# a= show offline gliders	1 - true, 0 -false
# &b={{ amax }}&c={{ amin }}&d={{ omax }}&e={{ omin }} (o : longitude and a : latitude
# &y={{ dt }}, "" for all types 
# dt	device type bitmask	0x1 ICAO, 0x2 Flarm, 0x4 OGN
	request = urllib.request.urlopen("http://live.glidernet.org/lxml.php?a=0&b=51.5&c=49.5&d=7.0&e=5.0")
	replyString = request.read().decode()
	xmlDoc = minidom.parseString(replyString)
except (urllib.error.HTTPError, urllib.error.URLError) as err:
	print("Cannot GET HTTP, error: ", err)
	sys.exit("Cannot GET HTTP, error: " + str(err))
except:
	print('Not found or invalid XML')
	sys.exit("Cannot get or parse XML, error: " + sys.exc_info()[0])

# <markers>
#<m a="50.818668,6.230770,EB,D-KEEB,713,13:14:56,7140,261,76,-4.6,1,AIRS89035,3E66E9,aebcb1ba"/>
#<m a="50.822979,6.191550,_45,5be20545,193,07:53:33,26423,0,0,0.1,8,EDKA,0,5be20545"/>
#</markers>


# Fields are: lat, long, CN (?), registration, altitude, last seen UTC, seconds since last seen, track, ground speed m/sec (or km/h), vz m/sec, aircraft type, receiver_name, address, code
# 1 = glider/motor glider
# 2 = tow/tug
# 3 = helicopter, rotorcraft
# 4 = skydiver
# 5 = drop plane for skydivers
# 6 = hand glider (hard)
# 7 = paraglider (soft)
# 8 = aircraft with reciprocating engine(s)
# 9 = aircraft with jet/turboprop engine(s)
# 10 = reserved
# 11 = balloon
# 12 = airship
# 13 = unmanned aerial vehicle (UAV)
# 14 = static object
# 15 = static object

# From https://github.com/TwinFan/LiveTraffic/issues/72
# aircraft_beacon.location.latitude,
# aircraft_beacon.location.longitude,
# competition,
# registration,
# aircraft_beacon.altitude,
# utc_to_local(aircraft_beacon.timestamp).strftime("%H:%M:%S"),
# elapsed_seconds,
# int(aircraft_beacon.track),
# int(aircraft_beacon.ground_speed),
# int(aircraft_beacon.climb_rate*10)/10,
# aircraft_beacon.aircraft_type, Aircraft type is likely the FLARM type, field ACFT as in the FLARM specs. http://ediatec.ch/pdf/FTD-014-FLARM-Configuration-Specification.pdf
# aircraft_beacon.receiver_name,
# address,
# code))


#..:: La carte ::..
#	Jaune: Planeurs et Motoplaneurs
#	Vert: Remorqueurs
#	Rouge: Hélicoptères
#	Rose: Deltaplanes et Parapentes
#	Noir: Drones et aéronefs sans pilote
#	Bleu: Autres aéronefs
#	Gris: Tout aéronef inactif (pas de donnée reçue depuis plus de 10 minutes)

prog = re.compile(r'(.+?),(.+?),(.*),(.*),(.+),(..:..:..),(.+?),(.+?),(.+?),(.+),(.+),(.+),.*')
today =  datetime.datetime.now(datetime.timezone.utc).strftime('%Y-%m-%d')

for marker in xmlDoc.getElementsByTagName('m'):
	aValue = marker.getAttribute('a')
	m = prog.match(aValue)
	latitude = m.group(1)
	longitude = m.group(2)
	tailNumber = urllib.parse.quote(m.group(4).replace('-', ''))
	altitude = round(float(m.group(5)) * 3.28084) # meter to feet conversion
	timeUTC = m.group(6)
	timeSinceMeasure = int(m.group(7))
	track = int(m.group(8))
	velocity = round(float(m.group(9)) * 0.539957) # km/h to knots conversion
	receiver_name = m.group(11)
	if receiver_name == 'SafeSky':
		source = 'SafeSky-OGN'
	else:
		source = 'OGN-evyncke'

#	print('Receiver name', receiver_name, ' source', source)
#	print('lat/lon', latitude, longitude)
#	print('ID', tailNumber)
#	print('Track', track)
#	print('GS', velocity)

	if timeSinceMeasure > 30 * 60:
		print("Skipping " + tailNumber + " as it is too old " + timeUTC)
		continue
	print(aValue)
	# Let's insert the glider data in the DB
	try:
		response = urllib.request.urlopen("https://www.spa-aviation.be/resa/add_to_tracks.php?local=yes&icao24=" + tailNumber + '&tail_number=' + tailNumber + '&daytime=' + urllib.parse.quote(today + ' ' + timeUTC) + '&longitude=' + longitude + '&latitude=' + latitude + '&altitude=' + str(altitude) + '&velocity=' + str(velocity) + '&track=' + str(track) + '&sensor=0&source=' + source)
		if response.status != 200:
			print('HTTP status', response.status)
			print('HTTP reason', response.reason)
			print('HTTP response', response.read())
	except (urllib.error.HTTPError, urllib.error.URLError) as e:
		print('Error when connecting to RAPCS: ' + str(e))
	except Exception as e:
		print('Error in connecting to RPACS web service: ' + str(e))
sys.exit()
