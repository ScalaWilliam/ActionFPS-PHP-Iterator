# Drakas listen script

import subprocess, pipes
from sseclient import SSEClient

# Reconnect automatically - it works, I checked it :D
while True:
	print("New connection")
	messages = SSEClient('http://api.actionfps.com/new-games/')
	# messages = SSEClient('http://api.actionfps.com/server-updates/')
	for message in messages:
		print(message)
		subprocess.call(["php", "take.php", str(message) ])
