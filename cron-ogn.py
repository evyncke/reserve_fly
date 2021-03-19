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
	request = urllib.request.urlopen("http://live.glidernet.org/lxml.php?a=1&b=51.5&c=49.5&d=7.0&e=5.0&y=15")
	replyString = request.read()
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


# Fields are: lat, long, CN (?), registration, altitude, last seen UTC, seconds since last seen, track, ground speed m/sec (or km/h), vz m/sec, ? dist receiver ?, receiver, device ID
# ? dist receiver ? or more aircraft type with
# 1 = glider/motor glider
# 3 = helicopter,
# 15 = bleu = static object


#..:: La carte ::..
#	Jaune: Planeurs et Motoplaneurs
#	Vert: Remorqueurs
#	Rouge: Hélicoptères
#	Rose: Deltaplanes et Parapentes
#	Noir: Drones et aéronefs sans pilote
#	Bleu: Autres aéronefs
#	Gris: Tout aéronef inactif (pas de donnée reçue depuis plus de 10 minutes)

prog = re.compile(r'(.+?),(.+?),(.*),(.*),(.+),(..:..:..),(.+?),(.+?),(.+?),(.+),.*')
today =  datetime.datetime.now(datetime.timezone.utc).strftime('%Y-%m-%d')

for marker in xmlDoc.getElementsByTagName('m'):
	aValue = marker.getAttribute('a')
	print(aValue)
	m = prog.match(aValue)
	latitude = m.group(1)
	longitude = m.group(2)
	tailNumber = m.group(4).replace('-', '')
	altitude = round(float(m.group(5)) * 3.28084) # meter to feet conversion
	timeUTC = m.group(6)
	timeSinceMeasure = int(m.group(7))
	track = int(m.group(8))
	velocity = round(float(m.group(9)) * 0.539957) # km/h to knots conversion

	print('lat/lon', latitude, longitude)
	print('ID', tailNumber)
	print('Track', track)
	print('GS', velocity)

	if timeSinceMeasure > 30 * 60:
		print("Skipping " + tailNumber + " as it is too old " + timeUTC)
		continue
	# Let's insert the glider data in the DB
	try:
		response = urllib.request.urlopen("https://www.spa-aviation.be/resa/add_to_tracks.php?local=yes&icao24=" + tailNumber + '&tail_number=' + tailNumber + '&daytime=' + urllib.parse.quote(today + ' ' + timeUTC) + '&longitude=' + longitude + '&latitude=' + latitude + '&altitude=' + str(altitude) + '&velocity=' + str(velocity) + '&track=' + str(track) + '&sensor=0&source=OGN')
		print('HTTP status', response.status)
		print('HTTP reason', response.reason)
		print('HTTP response', response.read())
	except (urllib.error.HTTPError, urllib.error.URLError) as e:
		print('Error when connecting to RAPCS: ' + str(e))
	except SocketError as e:
		print('Error in connecting to RPACS web service: ' + str(e))
sys.exit()
