## About
This directory holds templates that have static API URL endpoints. Use these to test an API token against many API service endpoints. By providing token input using flag, Nuclei will test the token against all known API endpoints within the API templates, and return any successful results. By incorporating API checks as Nuclei Templates, users can test API keys that have no context (i.e., API keys that do not indicate for which API endpoint they are meant).

## Usage
You do not need to specify an input URL to test a token against these API endpoints, as the API endpoints have static URLs. However, Nuclei requires an input (specified via `-u` for individual URLs or `-l` for a file containing URLs). Because of this requirement, we simply pass in `-u "null"`. Each template in the `token-spray` directory assumes the input API token will be provided using CLI `var` flag.

```bash
# Run Nuclei specifying all the api templates:

nuclei -u null -t token-spray/ -var token=thisIsMySecretTokenThatIWantToTest
```

## Credits
These API testing templates were inspired by the [streaak/keyhacks](https://github.com/streaak/keyhacks) repository. The Bishop Fox [Continuous Attack Surface Testing (CAST)](https://www.bishopfox.com/continuous-attack-surface-testing/how-cast-works/) team created additional API templates for testing API keys uncovered during investigations. You are welcome to add new templates based on the existing format to cover more APIs.

