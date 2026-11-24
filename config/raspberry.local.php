<?php

return <<<'RASPBERRY_SETTINGS'
# ===== Edit from this line down. =====
# The Pi picks up a change within 5 minutes. If there is an error here,
# the Pi ignores it and keeps running with the last valid settings.
# Write comments (#) on their own line only.

# --- Common for all cameras ---
PORT=554
YT_SERVER=rtmp://a.rtmp.youtube.com/live2

# --- Temperature and humidity (DHT22 sensors on the Pi) ---
# CLIMATE_URL  address of input.php; empty = temperature is not sent
# Sent every 5 minutes. Sensor GPIO pins are set on the Pi (installer with --sensors).
CLIMATE_URL=https://slepice.twomedia.cz/input.php

# --- Cameras: each has its own block [name] ---
# ACTIVE       yes = streaming, no = switched off (the other values may then stay empty)
# IP           camera address on the local network
# USER         camera account user name (Tapo app � Camera Account)
# PASSWORD     password of that account; no spaces and none of  @ : / # ? % \ " ' $
# STREAM       stream1 = full resolution, stream2 = 720p
#              (one Tapo camera serves at most 2� stream1 and 2� stream2 at once)
# AUDIO        yes = audio from the camera, no = silent track (optional, default no)
# YOUTUBE_KEY  stream key from YouTube Studio; empty = the camera does not stream
# New block = new camera, deleted block = the camera is switched off.
# Block names: ASCII letters, digits, - and _ only.

[camera1]
ACTIVE=yes
IP=10.84.2.148
USER=SlepiceDoloplazy4342
PASSWORD=Doloplazy434.
STREAM=stream1
AUDIO=no
YOUTUBE_KEY=223z-h33f-adt9-xxtz-4dmd

[camera2]
ACTIVE=yes
IP=10.84.2.49
USER=SlepiceDoloplazy4342
PASSWORD=Doloplazy434.
STREAM=stream1
AUDIO=no
YOUTUBE_KEY=1d1c-q9ts-d72p-2zrk-dm9g

[camera3]
ACTIVE=yes
IP=10.84.2.91
USER=SlepiceDoloplazy4342
PASSWORD=Doloplazy434.
STREAM=stream1
AUDIO=yes
YOUTUBE_KEY=5prj-qvuy-d7be-x413-6z7w

RASPBERRY_SETTINGS;
