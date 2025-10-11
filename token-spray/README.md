## About

This directory holds templates that have static API URL endpoints. Use these to test an API token against many API service endpoints. By providing token input using flag, Nuclei will test the token against all known API endpoints within the API templates, and return any successful results. By incorporating API checks as Nuclei Templates, users can test API keys that have no context (i.e., API keys that do not indicate for which API endpoint they are meant).

## Usage

token-spray are **self-contained** template and does not requires URLs as input as the API endpoints have static URLs predefined in the template. Each template in the `token-spray` directory assumes the input API token/s will be provided using CLI `var` flag.

```console
# Running token-spray templates against a single token to test
nuclei -t token-spray/ -var token=random-token-to-test

# Running token-spray templates against a file containing multiple new line delimited tokens
nuclei -t token-spray/ -var token=file_with_tokens.txt
```

## Credits

These API testing templates were inspired by the [streaak/keyhacks](https://github.com/streaak/keyhacks) repository. The Bishop Fox [Continuous Attack Surface Testing (CAST)](https://www.bishopfox.com/continuous-attack-surface-testing/how-cast-works/) team created additional API templates for testing API keys uncovered during investigations. You are welcome to add new templates based on the existing format to cover more APIs.