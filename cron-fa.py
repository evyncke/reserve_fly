#!/bin/env python3
import urllib.request
import urllib
import json
import datetime
import errno
import socket
import math

# https://github.com/flightaware/dump1090/blob/master/README-json.md

my_icao = [ '448585', '4499b8', '44aa42', '448584', '484b0c', '44ce10']
my_icao.append('3d6745') # D-FLIZ SkyDive
my_icao.append('449a89') # OO-FTI
my_icao.append('44b1a9') # OO-LMI SkyDive helicopter ?
my_icao.append('44ccb8') # OO-SEX SkyDive
my_icao.append('44ce01') # OO-SPA SkyDive
my_icao.append('44d1f0') # OO-TOP SkyDive ?
my_fa_site_fqdn = 'raspeberry.local'
my_fa_site_fqdn = 'localhost' 
my_fa_site_port = 8080 

# Translation of ICAO 24-bit code into registration number
# based on https://github.com/adsbxchange/dump1090-fa/blob/master/public_html/registrations.js

class mapping:
	def __init__(self, start, s1, s2, prefix, first=None, last=None):
		self.alphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ"
		self.start = start
		self.s1 = s1
		self.s2 = s2
		self.prefix = prefix
		self.first = first
		self.last = last
		if self.first:
		# do the real stuff !
			self.offset = 0
		else:
			self.offset = 0
		if self.last:
			self.end = 999 # do the real stuff
		else:
			self.end = self.start - self.offset + s1 * (len(self.alphabet)-1) + s2 * (len(self.alphabet)-1) + len(self.alphabet) - 1

	def applicable(self, n):
		return (self.start <= n and n <= self.end)

# Should try to find the mapping
# 484871 PHOTK 
# 484b0c PHAML 
# 485613 PHDEC

# Netherlands       0100 1000 0 ... .. ..........   480000 - 487FFF   22000000 - 22077777
#
# A = 0
# M = 12
# L = 11
# With 26*26 and 26 = AML = 0 * 26*26 + 12 * 26 + 11 = 323 (start =  0x484b0c - 323 = 0x4849C9
# With 1024 and 32 = AML = 0 * 1024 + 12 * 32 + 11 = 395 = 0x18b (start = 0x484b0c - 0x18b = 0x484981
mappings = []
mappings.append(mapping(0x008011, 26*26, 26, prefix="ZS"))
mappings.append(mapping(0x390000, 1024, 32, prefix="FG"))
mappings.append(mapping(0x398000, 1024, 32, prefix="FH"))
mappings.append(mapping(0x3CC000, 26*26, 26, prefix="DC"))
mappings.append(mapping(0x3D04A8, 26*26, 26, prefix="DE"))
mappings.append(mapping(0x3D4950, 26*26, 26, prefix="DF"))
mappings.append(mapping(0x3D8DF8, 26*26, 26, prefix="DG"))
mappings.append(mapping(0x3DD2A0, 26*26, 26, prefix="DH"))
mappings.append(mapping(0x3E1748, 26*26, 26, prefix="DI"))
mappings.append(mapping(0x448421, 1024,  32, prefix="OO"))  # 32768 codes as in 0 1 0 0 : 0 1 0 0 : 1 - - - - - - - - - - - - - - -
mappings.append(mapping(0x458421, 1024,  32, prefix="OY"))
mappings.append(mapping(0x460000, 26*26, 26, prefix="OH"))
mappings.append(mapping(0x468421, 1024,  32, prefix="SX"))
mappings.append(mapping(0x490421, 1024,  32, prefix="CS"))
mappings.append(mapping(0x4A0421, 1024,  32, prefix="YR"))
mappings.append(mapping(0x4B8421, 1024,  32, prefix="TC"))
mappings.append(mapping(0x740421, 1024,  32, prefix="JY"))
mappings.append(mapping(0x760421, 1024,  32, prefix="AP"))
mappings.append(mapping(0x768421, 1024,  32, prefix="9V"))
mappings.append(mapping(0x778421, 1024,  32, prefix="YK"))
mappings.append(mapping(0xC00001, 26*26, 26, prefix="CF"))
mappings.append(mapping(0xC044A9, 26*26, 26, prefix="CG"))
mappings.append(mapping(0xE01041, 4096,  64, prefix="LV"))

#mappings.append(mapping(0x484981, 1024,  32, prefix="PH"))
#mappings.append(mapping(0x4849C9, 26*26,  26, prefix="PH"))

limitedAlphabet = "ABCDEFGHJKLMNPQRSTUVWXYZ" # 24 chars; no I, O

def n_letter(rem):
	if rem == 0:
		return ''
	return limitedAlphabet[rem]

def n_letters(rem):
	if rem == 0:
		return ''
	return limitedAlphabet[math.floor(rem/25)] + n_letter(rem % 25)

def lookup(icao24):
	try:
		hex = int('0x' + icao24, base=16)
	except:
		return icao24
	for mapping in mappings:
		if not mapping.applicable(hex):
			continue
		offset = hex - mapping.start + mapping.offset
		i1 = math.floor(offset / mapping.s1)
		offset = offset % mapping.s1
		i2 = math.floor(offset / mapping.s2)
		offset = offset % mapping.s2
		i3 = offset
		if i1 >= len(mapping.alphabet) or i2 >= len(mapping.alphabet) or i3 >= len(mapping.alphabet):
			print("!!!!! invalid mapping found !!!!")
			return icao24
		print("--- mapping striving found")
		return mapping.prefix + mapping.alphabet[i1] + mapping.alphabet[i2] + mapping.alphabet[i3]

	# Let's try to US N--- registration
	offset = hex - 0xA00001
	if 0 <= offset and offset <= 915399:
		i1 = math.floor(offset / 101711) + 1
		reg = 'N' + str(i1)
		offset = offset % 101711
		if offset <= 600:
			return reg + n_letters(offset)
		print("--- mapping US found " + reg)
		return reg
	print(">>> no mapping found for " + icao24)
	return icao24

#print("PH-AML => " + lookup('484b0c'))
#print("PH-OTK => " + lookup('484871'))
#print("OO-TOP => " + lookup('44d1f0'))
#import sys
#sys.exit("Early")

request = urllib.request.urlopen("http://" + my_fa_site_fqdn + ":" + str(my_fa_site_port) + '/data/aircraft.json')

response = json.loads(request.read())
now = datetime.datetime.fromtimestamp(response['now'], tz=datetime.timezone.utc).strftime('%Y-%m-%d %H:%M:%S') # It is now in local time while it should be in UTC
aircrafts = response['aircraft']
for aircraft in aircrafts:
	aircraft['hex'] = aircraft['hex'].lower()  # just in case...
	if not 'alt_baro' in aircraft: 
		aircraft['alt_baro'] = -1
	elif aircraft['alt_baro'] == 'ground':
		aircraft['alt_baro'] = 0
	else:
		aircraft['alt_baro'] = int(aircraft['alt_baro'])
	if not 'gs' in aircraft: aircraft['gs'] = -1
	if not 'track' in aircraft: aircraft['track'] = -1
	if not 'squawk' in aircraft: aircraft['squawk'] = '----'
	if not 'flight' in aircraft: aircraft['flight'] = lookup(aircraft['hex'])
	if aircraft['flight'] == '-': aircraft['flight'] = lookup(aircraft['hex'])
	aircraft['flight'] = aircraft['flight'].strip()
	if aircraft['hex'] in my_icao:
		print('Found my aircraft at ' + now)
		print(aircraft)
		if 'lon' in aircraft:
			try:
				response = urllib.request.urlopen("https://www.spa-aviation.be/resa/add_to_tracks.php?icao24=" + aircraft['hex'] + '&tail_number=' + aircraft['flight'] + '&daytime=' + urllib.parse.quote(now) + '&longitude=' + str(aircraft['lon']) + '&latitude=' + str(aircraft['lat']) + '&altitude=' + str(aircraft['alt_baro']) + '&velocity=' + str(aircraft['gs']) + '&squawk=' + urllib.parse.quote(aircraft['squawk']) + '&track=' + str(aircraft['track']) + '&sensor=0&source=FA-evyncke')
			except urllib.error.URLError as e:
				print('Error when connecting to RAPCS: ' + str(e))
			except SocketError as e:
				print('Error in connecting to RAPCS web service: ' + str(e))
		else:
			print('Alas no location...')
	if (aircraft['alt_baro'] > 0 and aircraft['alt_baro'] <= 5000): # it is a local flight it seems
		print('Found a nearby aircraft at ' + now)
		print(aircraft)
		if 'lon' in aircraft:
			try:
				response = urllib.request.urlopen("https://www.spa-aviation.be/resa/add_to_tracks.php?local=yes&icao24=" + aircraft['hex'] + '&daytime=' + urllib.parse.quote(now) + '&longitude=' + str(aircraft['lon']) + '&latitude=' + str(aircraft['lat']) + '&altitude=' + str(aircraft['alt_baro']) + '&velocity=' + str(aircraft['gs']) + '&tail_number=' + urllib.parse.quote(aircraft['flight']) + '&track=' + str(aircraft['track']) + '&source=FA-evyncke')
				print("https://www.spa-aviation.be/resa/add_to_tracks.php?local=yes&icao24=" + aircraft['hex'] + '&daytime=' + urllib.parse.quote(now) + '&longitude=' + str(aircraft['lon']) + '&latitude=' + str(aircraft['lat']) + '&altitude=' + str(aircraft['alt_baro']) + '&velocity=' + str(aircraft['gs']) + '&tail_number=' + urllib.parse.quote(aircraft['flight']) + '&track=' + str(aircraft['track']) + '&source=FA-evyncke')
			except urllib.error.URLError as e:
				print('Error when connecting to RAPCS: ' + str(e))
			except SocketError as e:
				print('Error in connecting to RAPCS web service: ' + str(e))
		else:
			print('Alas no location...')
	else:
		print('Not a useful aircraft ' + aircraft['flight'])
