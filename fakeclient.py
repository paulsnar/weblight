#!/usr/bin/env python3

import socket
import struct

PREFIX = b'weblight'
HEADER = struct.Struct('>L')
PROGRAM = struct.Struct('>8cL')

def main():
  s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
  s.connect(('127.0.0.1', 5448))

  assert s.recv(8) == PREFIX
  print('connected')
  s.send(PREFIX)
  while True:
    try:
      header = s.recv(4)
    except KeyboardInterrupt:
      s.close()
      raise

    if len(header) == 0:
      print('disconnected')
      s.close()
      return
    cmd, = HEADER.unpack(header)
    print(f'cmd: {cmd}')
    if cmd == 1:
      program = s.recv(12)
      program = PROGRAM.unpack(program)
      id = ''.join(x.decode('ascii') for x in program[:8])
      rev = program[8]
      print(f'program: {id}, rev {rev}')

if __name__ == '__main__':
  main()
