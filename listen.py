# Drakas listen script

import subprocess
from subprocess import Popen, PIPE, STDOUT
from sseclient import SSEClient

# Reconnect automatically - it works, I checked it :D
while True:
	print("New connection")
	messages = SSEClient('http://api.actionfps.com/new-games/')
	# messages = SSEClient('http://api.actionfps.com/server-updates/')
	for message in messages:
		print(message)
		p = Popen(['php', 'take.php'], stdout=PIPE, stdin=PIPE, stderr=PIPE)
		p.communicate(input=str(message))
