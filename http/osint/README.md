## Description

Nuclei-templates provide a comprehensive suite of security checks, including OSINT templates in this directory for user-enumeration and phishing templates for the identification and analysis of phishing sites. 

The **User Enumeration templates** are tailored for user enumeration across various websites, allowing Nuclei to verify user existence. They expect input such as username, email, or phone number through the `V`/`var` flag.

The **Phishing templates** are crafted for detecting and analyzing phishing sites. These templates are essential for OSINT analysts, threat researchers, and security professionals to uncover and study phishing campaigns. 

## Usage

These templates are specifically added to help OSINT analysts, threat researchers therefore, we have added them to the OSINT scan profile [here](https://github.com/projectdiscovery/nuclei-templates/blob/main/config/osint.yml). 
Users can execute the OSINT scan configuration profile with the following command:

```console
nuclei -u <host> -config ~/nuclei-templates/config/osint.yml
```
