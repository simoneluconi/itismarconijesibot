#Importo le librerie neccessarie
import os, threading, urllib.request
from lxml import etree, html
import ctypes
import datetime

ctypes.windll.kernel32.SetConsoleTitleW("ITIS Bot Check")

class AppURLopener(urllib.request.FancyURLopener):
    version = "Mozilla/5.0"

opener = AppURLopener()

def chiama():
    threading.Timer(1800, chiama).start()
    response = opener.open('https://www.simoneluconi.com/telegram/itismarconijesibot/index.php') 
    code = response.getcode()
    if (code == 200):
     print(str(datetime.datetime.now()) + ' Response: ' + str(code) + ' :)')
     result = response.read()
     response.close()
    else: 
     print(str(datetime.datetime.now()) + ' Response: ' + str(code) + ' :(')
     
chiama()
