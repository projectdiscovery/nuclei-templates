Templates are the core of [nuclei scanner](https://github.com/projectdiscovery/nuclei) which power the actual scanning engine. This repository stores and houses various templates for the scanner provided by our team as well as contributed by the community. We hope that you also contribute by sending templates via **pull requests** and grow the list.

<details>
<summary>Template Directory</summary>

```
├── LICENSE
├── README.md
├── basic-detections
│   ├── basic-xss-prober.yaml
│   └── general-tokens.yaml
├── brute-force
│   └── tomcat-manager-bruteforce.yaml
├── cves
│   ├── CVE-2017-10075.yaml
│   ├── CVE-2017-7529.yaml
│   ├── CVE-2017-9506.yaml
│   ├── CVE-2017-9841.yaml
│   ├── CVE-2018-0296.yaml
│   ├── CVE-2018-1000129.yaml
│   ├── CVE-2018-11409.yaml
│   ├── CVE-2018-11759.yaml
│   ├── CVE-2018-1247.yaml
│   ├── CVE-2018-1271.yaml
│   ├── CVE-2018-13379.yaml
│   ├── CVE-2018-14728.yaml
│   ├── CVE-2018-16341.yaml
│   ├── CVE-2018-18069.yaml
│   ├── CVE-2018-19439.yaml
│   ├── CVE-2018-20824.yaml
│   ├── CVE-2018-2791.yaml
│   ├── CVE-2018-3714.yaml
│   ├── CVE-2018-3760.yaml
│   ├── CVE-2018-5230.yaml
│   ├── CVE-2018-7490.yaml
│   ├── CVE-2019-10475.yaml
│   ├── CVE-2019-11510.yaml
│   ├── CVE-2019-12314.yaml
│   ├── CVE-2019-14322.yaml
│   ├── CVE-2019-14974.yaml
│   ├── CVE-2019-15043.yaml
│   ├── CVE-2019-16759.yaml
│   ├── CVE-2019-17382.yaml
│   ├── CVE-2019-18394.yaml
│   ├── CVE-2019-19368.yaml
│   ├── CVE-2019-19781.yaml
│   ├── CVE-2019-19908.yaml
│   ├── CVE-2019-19985.yaml
│   ├── CVE-2019-2588.yaml
│   ├── CVE-2019-3396.yaml
│   ├── CVE-2019-3799.yaml
│   ├── CVE-2019-5418.yaml
│   ├── CVE-2019-8449.yaml
│   ├── CVE-2019-8451.yaml
│   ├── CVE-2019-8903.yaml
│   ├── CVE-2019-8982.yaml
│   ├── CVE-2020-10199.yaml
│   ├── CVE-2020-10204.yaml
│   ├── CVE-2020-1147.yaml
│   ├── CVE-2020-12720.yaml
│   ├── CVE-2020-13167.yaml
│   ├── CVE-2020-2096.yaml
│   ├── CVE-2020-3187.yaml
│   ├── CVE-2020-3452.yaml
│   ├── CVE-2020-5284.yaml
│   ├── CVE-2020-5405.yaml
│   ├── CVE-2020-5410.yaml
│   ├── CVE-2020-5902.yaml
│   ├── CVE-2020-6287.yaml
│   ├── CVE-2020-7209.yaml
│   ├── CVE-2020-7961.yaml
│   ├── CVE-2020-8091.yaml
│   ├── CVE-2020-8115.yaml
│   ├── CVE-2020-8191.yaml
│   ├── CVE-2020-8193.yaml
│   ├── CVE-2020-8194.yaml
│   ├── CVE-2020-8512.yaml
│   ├── CVE-2020-8982.yaml
│   ├── CVE-2020-9484.yaml
│   └── CVE-2020-9757.yaml
├── dns
│   ├── azure-takeover-detection.yaml
│   ├── cname-service-detector.yaml
│   ├── dead-host-with-cname.yaml
│   └── servfail-refused-hosts.yaml
├── files
│   ├── apc-info.yaml
│   ├── cgi-test-page.yaml
│   ├── debug-pprof.yaml
│   ├── dir-listing.yaml
│   ├── docker-registry.yaml
│   ├── drupal-install.yaml
│   ├── elasticsearch.yaml
│   ├── exposed-kibana.yaml
│   ├── exposed-svn.yaml
│   ├── filezilla.yaml
│   ├── firebase-detect.yaml
│   ├── git-config.yaml
│   ├── htaccess-config.yaml
│   ├── jkstatus-manager.yaml
│   ├── jolokia.yaml
│   ├── laravel-env.yaml
│   ├── lazy-file.yaml
│   ├── phpinfo.yaml
│   ├── public-tomcat-instance.yaml
│   ├── security.txt.yaml
│   ├── server-status-localhost.yaml
│   ├── telerik-dialoghandler-detect.yaml
│   ├── telerik-fileupload-detect.yaml
│   ├── tomcat-scripts.yaml
│   ├── wadl-files.yaml
│   ├── web-config.yaml
│   ├── wordpress-directory-listing.yaml
│   ├── wordpress-user-enumeration.yaml
│   ├── wp-xmlrpc.yaml
│   └── zip-backup-files.yaml
├── panels
│   ├── atlassian-crowd-panel.yaml
│   ├── cisco-asa-panel.yaml
│   ├── citrix-adc-gateway-detect.yaml
│   ├── compal.yaml
│   ├── crxde.yaml
│   ├── docker-api.yaml
│   ├── fortinet-fortigate-panel.yaml
│   ├── globalprotect-panel.yaml
│   ├── grafana-detect.yaml
│   ├── jenkins-asyncpeople.yaml
│   ├── jmx-console.yaml
│   ├── kubernetes-pods.yaml
│   ├── mongo-express-web-gui.yaml
│   ├── parallels-html-client.yaml
│   ├── phpmyadmin-panel.yaml
│   ├── pulse-secure-panel.yaml
│   ├── rabbitmq-dashboard.yaml
│   ├── sap-netweaver-detect.yaml
│   ├── sap-recon-detect.yaml
│   ├── sophos-fw-version-detect.yaml
│   ├── supervpn-panel.yaml
│   ├── swagger-panel.yaml
│   ├── tikiwiki-cms.yaml
│   ├── weave-scope-dashboard-detect.yaml
│   └── webeditors.yaml
├── payloads
│   └── CVE-2020-6287.xml
├── security-misconfiguration
│   ├── basic-cors-flash.yaml
│   ├── basic-cors.yaml
│   ├── front-page-misconfig.yaml
│   ├── jira-service-desk-signup.yaml
│   ├── jira-unauthenticated-dashboards.yaml
│   ├── jira-unauthenticated-popular-filters.yaml
│   ├── jira-unauthenticated-projects.yaml
│   ├── jira-unauthenticated-user-picker.yaml
│   ├── rabbitmq-default-admin.yaml
│   ├── rack-mini-profiler.yaml
│   ├── springboot-detect.yaml
│   └── wamp-xdebug-detect.yaml
├── subdomain-takeover
│   ├── detect-all-takeovers.yaml
│   └── s3-subtakeover.yaml
├── technologies
│   ├── bigip-config-utility-detect.yaml
│   ├── citrix-vpn-detect.yaml
│   ├── clockwork-php-page.yaml
│   ├── couchdb-detect.yaml
│   ├── github-enterprise-detect.yaml
│   ├── gitlab-detect.yaml
│   ├── graphql.yaml
│   ├── home-assistant.yaml
│   ├── jaspersoft-detect.yaml
│   ├── jira-detect.yaml
│   ├── liferay-portal-detect.yaml
│   ├── linkerd-badrule-detect.yaml
│   ├── linkerd-ssrf-detect.yaml
│   ├── netsweeper-webadmin-detect.yaml
│   ├── ntlm-directories.yaml
│   ├── prometheus-exposed-panel.yaml
│   ├── s3-detect.yaml
│   ├── sap-netweaver-as-java-detect.yaml
│   ├── sap-netweaver-detect.yaml
│   ├── sql-server-reporting.yaml
│   ├── tech-detect.yaml
│   ├── weblogic-detect.yaml
│   └── werkzeug-debugger-detect.yaml
├── tokens
│   ├── amazon-mws-auth-token-value.yaml
│   ├── aws-access-key-value.yaml
│   ├── google-api-key.yaml
│   ├── http-username-password.yaml
│   ├── mailchimp-api-key.yaml
│   └── slack-access-token.yaml
├── vulnerabilities
│   ├── cached-aem-pages.yaml
│   ├── couchdb-adminparty.yaml
│   ├── crlf-injection.yaml
│   ├── discourse-xss.yaml
│   ├── git-config-nginxoffbyslash.yaml
│   ├── ibm-infoprint-directory-traversal.yaml
│   ├── microstrategy-ssrf.yaml
│   ├── moodle-filter-jmol-lfi.yaml
│   ├── moodle-filter-jmol-xss.yaml
│   ├── nginx-module-vts-xss.yaml
│   ├── open-redirect.yaml
│   ├── oracle-ebs-bispgraph-file-access.yaml
│   ├── pdf-signer-ssti-to-rce.yaml
│   ├── rce-shellshock-user-agent.yaml
│   ├── rce-via-java-deserialization.yaml
│   ├── springboot-actuators-jolokia-xxe.yaml
│   ├── symfony-debugmode.yaml
│   ├── tikiwiki-reflected-xss.yaml
│   ├── tomcat-manager-pathnormalization.yaml
│   ├── twig-php-ssti.yaml
│   ├── wordpress-duplicator-path-traversal.yaml
│   ├── wordpress-wordfence-xss.yaml
│   └── x-forwarded-host-injection.yaml
└── workflows
    ├── bigip-pwner-workflow.yaml
    ├── jira-exploitaiton-workflow.yaml
    ├── liferay-rce-workflow.yaml
    ├── netsweeper-preauth-rce-workflow.yaml
    ├── rabbitmq-workflow.yaml
    ├── sap-netweaver-workflow.yaml
    └── springboot-pwner-workflow.yaml
```

</details>

13 directories, **204 templates**. 

Please navigate to https://nuclei.projectdiscovery.io for detailed documentation to build new and your own custom templates and many example templates for easy understanding. 

------
**Notes:** 
1. Use YAMLlint (e.g. [yamllint](http://www.yamllint.com/)) to validate new templates when sending pull requests.
2. Use YAML Formatter (e.g. [jsonformatter](https://jsonformatter.org/yaml-formatter)) to format new templates when sending pull requests.

Thanks again for your contribution and keeping the community vibrant. :heart:
