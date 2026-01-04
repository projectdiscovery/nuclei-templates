# CVE-2018-9206 jQuery-File-Upload Vulnerability Testing Environment

This directory contains a Docker environment for testing the CVE-2018-9206 nuclei template.

## Vulnerable Environment

The environment uses Blueimp jQuery-File-Upload v9.22.0 which is vulnerable to unauthenticated arbitrary file upload.

## Building and Running

```bash
cd docker/vulnerable-test
docker-compose up --build
```

## Testing the Template

Once the vulnerable environment is running:

```bash
# Test with nuclei
nuclei -t http/cves/2018/CVE-2018-9206.yaml -target http://localhost:8080 -debug
```

## Expected Results

The template should:
1. Send a multipart POST request to upload a test HTML file
2. Extract the URL from the server's JSON response
3. Verify the uploaded file is accessible
4. Report a critical vulnerability if successful

## Debug Output

When testing, you should see:
- POST request to `/index.php` with multipart form data
- 200 OK response with JSON containing upload details
- Extracted upload URL in the format `http://localhost:8080/files/{random}.html`
- Verification GET request to confirm file accessibility
