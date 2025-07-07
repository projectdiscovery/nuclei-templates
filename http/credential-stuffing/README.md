## Credential Stuffing Templates

![credential-stuffing](https://github.com/projectdiscovery/nuclei-templates/assets/28601533/50ed7f7e-7da7-4708-8747-39b207eb8b52)


This directory contains a collection of credential stuffing templates for both cloud and self-hosted services. These templates help automate the detection and prevention of credential stuffing attempts on your organization's websites and applications using the Nuclei vulnerability scanner.

### Types of Templates

- **Cloud Services**: Templates for credential stuffing testing on cloud service providers.
- **Self-Hosted Services**: Templates for credential stuffing testing on self-hosted software instances that often have custom hosting environments.

### Usage

#### Cloud Services Template

An example of using a cloud service credential stuffing template can be seen with the Datadog Login Check template:

```bash
nuclei -var username=testing@projectdiscovery.io -var password=test123 -id datadog-login-check
```

Here, the `-var` option supplies the necessary inputs (username/email and password) to the template.

#### Self-Hosted Services Template

An example of using a self-hosted service credential stuffing template can be seen with the Jira Login Check template:

```bash
nuclei -u https://jira.projectdiscovery.io/ -id jira-login-check -var username=testing@projectdiscovery.io -var password=test123 
```

In this case, you also need to provide the hostname/IP of the deployed instance using the `-u` or `--url` option along with the necessary credentials using the `-var` option.

### Attack Types

By default, Nuclei uses Pitchfork mode in which it takes the first line from `email.txt` as the username input and the first line from `pass.txt` as the password parameter input. Ensure that both `email.txt` and `pass.txt` have an equal number of entries, with email/password combinations aligned on the same line in both files.

Starting with Nuclei 2.8, you can override the default behavior using the `-at` or `-attack-type` CLI option. Specifying the attack-type option as `clusterbomb` enables convenient verification of weak credentials for a list of given email addresses across various services.

For example, assuming `email.txt` contains:

```
email1@example.com
email2@example.com
email3@example.com
```

And `pass.txt` contains:

```
password1
password2
password3
```

The command below will check credential validity by sequentially testing each email from `email.txt` with all entries in `pass.txt` across different hosts stored in `jira.txt`:

```bash
cat jira.txt | nuclei -var username=email.txt -var password=pass.txt -id jira-login-check -attack-type clusterbomb
```

Developing custom target-specific templates for internal/custom portals can yield even more comprehensive results.

### Contributing and Updating Templates

Help us improve the credential stuffing templates by contributing new templates, reporting bugs, or requesting new features. Contributions are most welcome!


Fix issues, add new templates, and update existing ones by submitting a pull request. Always adhere to the best practices for YAML syntax and ensure that your template is tested before submitting.

Please refer to the template documentation to learn more about writing and submitting new templates to this repository: https://nuclei.projectdiscovery.io/templating-guide/
