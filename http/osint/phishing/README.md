## Description

This directory contains a collection of templates designed for the identification and analysis of phishing sites. These templates are specifically created to help OSINT analysts, threat researchers, and security professionals in discovering and studying phishing campaigns. 

## Usage

The phishing templates are designed for targeted use and are not included in Nuclei's default scans. To incorporate these templates into your scan, you can specify them using the `-itags` flags as follows:

```console
nuclei -u <host> -tags phishing -itags phishing
```

For users interested in comprehensive Open Source Intelligence (OSINT) gathering, these phishing templates have been integrated into the OSINT scan profile. This enables a more detailed and focused analysis as part of broader security research efforts or investigative journalism. 
To execute the OSINT scan configuration profile, which includes phishing checks among other templates, use the following command:

```console
# Execute the OSINT scan configuration profile
nuclei -u <host> -config ~/nuclei-templates/config/osint.yml
```

The integration of phishing templates into the OSINT scan profile allows for a more nuanced and in-depth approach to security research, aiding in the detection of emerging threats and the analysis of ongoing phishing campaigns.
