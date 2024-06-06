## About

This directory hosts Nuclei configuration profiles specifically designed for various use cases, including Bug Bounty, OSINT, and Compliance. The centerpiece of these configurations is the `recommended.yml` file, which offers a handpicked selection of templates that are both efficient and relevant for the majority of scanning scenarios. This curated approach is intended to provide a more focused scanning experience, reducing the occurrence of irrelevant results that often accompany broader scans.

## Usage

The Nuclei configuration profiles are straightforward to integrate into your existing scanning workflows. Below are guidelines on how to utilize the `recommended.yml` configuration for a streamlined scanning process, as well as instructions for customizing your scans to fit specific needs.

### Using the Recommended Configuration

To execute a scan with the `recommended.yml` configuration, which has been optimized for general use to yield efficient and relevant results, use the following command:

```
nuclei -config ~/nuclei-templates/profiles/recommended.yml
```

## Customizing Your Scanning Configuration
If you have specific requirements or wish to modify the focus of your scans, you can create a custom configuration file based on the structure of recommended.yml. Adjust the template selections to fit your targeted scanning objectives. Once your configuration is set, run Nuclei using your custom file with the command:

```
nuclei -config your-custom-config.yml
```

## Examples

Here are examples of how to run scans for specific scenarios:

#### Running Local Privilege Escalation Checks
For targeting local privilege escalation vulnerabilities, utilize the dedicated config as follows:

```
nuclei -config ~/nuclei-templates/profiles/privilege-escalation.yml
```

#### Config Focusing on OSINT

```
nuclei -config ~/nuclei-templates/profiles/osint.yml
```
