#Importo le librerie neccessarie
import os, threading, urllib.request
from lxml import etree, html

class AppURLopener(urllib.request.FancyURLopener):
    version = "Mozilla/5.0"

opener = AppURLopener()

def chiama():
    threading.Timer(1800, chiama).start()
    response = opener.open('https://www.simoneluconi.com/telegram/itismarconijesibot/index.php') 
    code = response.getcode()
    if (code == 200):
     print('Response: ' + str(code) + ' :D')
     result = response.read()
     response.close()
    else: 
     print('Response: ' + str(code) + ' :(')
     
chiama()
