import os

# The content of the test file is a mix of binary and text.
# We will write it in binary mode.
# The file will have the RAR5 magic number, some padding, and the path traversal payload.
# This is not a valid RAR file, but it is enough to test the template.

with open("test.rar", "wb") as f:
    # RAR5 magic number: 52 61 72 21 1A 07 01 00
    f.write(b'\x52\x61\x72\x21\x1a\x07\x01\x00')
    # Some padding to avoid EOF
    f.write(b'A' * 2048)
    # The path traversal payload
    f.write(b'..\\' * 10)

# Create a non-malicious file for control testing
with open("legit.rar", "wb") as f:
    f.write(b'\x52\x61\x72\x21\x1a\x07\x01\x00')
    f.write(b'B' * 2048)
