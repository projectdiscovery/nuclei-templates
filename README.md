
# Nuclei Templates

[![License](https://img.shields.io/badge/license-MIT-_red.svg)](https://opensource.org/licenses/MIT)
[![GitHub Release](https://img.shields.io/github/release/projectdiscovery/nuclei-templates)](https://github.com/projectdiscovery/nuclei-templates/releases)
[![contributions welcome](https://img.shields.io/badge/contributions-welcome-brightgreen.svg?style=flat)](https://github.com/projectdiscovery/nuclei-templates/issues)
[![Follow on Twitter](https://img.shields.io/twitter/follow/pdnuclei.svg?logo=twitter)](https://twitter.com/pdnuclei)
[![Chat on Discord](https://img.shields.io/discord/695645237418131507.svg?logo=discord)](https://discord.gg/KECAGdH)

Templates are the core of [nuclei scanner](https://github.com/projectdiscovery/nuclei) which power the actual scanning engine. This repository stores and houses various templates for the scanner provided by our team as well as contributed by the community. We hope that you also contribute by sending templates via **pull requests** or [Github issue](https://github.com/projectdiscovery/nuclei-templates/issues/new?assignees=&labels=&template=submit-template.md&title=%5Bnuclei-template%5D+) and grow the list.

An overview of the nuclei template directory including number of templates and HTTP request associated with each directory. 

### Nuclei templates `v7.2.7` overview

| Templates | Counts | Templates | Counts |
|----|----|----|----|
| cves | 137 | files | 46 |
| vulnerabilities | 39 | panels | 40 |
| technologies | 38 | security-misconfiguration | 28 |
| workflows | 16 | tokens | 8 |
| dns | 6 | fuzzing | 6 |
| generic-detections | 3 | default-credentials | 3 |
| subdomain-takeover | 2 | payloads | 2 |
| wordlists | 1 |


### Nuclei templates `v7.2.7` tree overview

<details>
<summary> Nuclei templates </summary>

```
├── LICENSE.md
├── README.md
├── cves
│   ├── CVE-2005-2428.yaml
│   ├── CVE-2013-2251.yaml
│   ├── CVE-2014-6271.yaml
│   ├── CVE-2017-10075.yaml
│   ├── CVE-2017-11444.yaml
│   ├── CVE-2017-12637.yaml
│   ├── CVE-2017-14537.yaml
│   ├── CVE-2017-14849.yaml
│   ├── CVE-2017-5638.yaml
│   ├── CVE-2017-7391.yaml
│   ├── CVE-2017-7615.yaml
│   ├── CVE-2017-9506.yaml
│   ├── CVE-2017-9841.yaml
│   ├── CVE-2018-0296.yaml
│   ├── CVE-2018-1000129.yaml
│   ├── CVE-2018-11409.yaml
│   ├── CVE-2018-11759.yaml
│   ├── CVE-2018-1247.yaml
│   ├── CVE-2018-1271.yaml
│   ├── CVE-2018-1273.yaml
│   ├── CVE-2018-13379.yaml
│   ├── CVE-2018-14728.yaml
│   ├── CVE-2018-16341.yaml
│   ├── CVE-2018-16763.yaml
│   ├── CVE-2018-17431.yaml
│   ├── CVE-2018-18069.yaml
│   ├── CVE-2018-19386.yaml
│   ├── CVE-2018-19439.yaml
│   ├── CVE-2018-20824.yaml
│   ├── CVE-2018-2791.yaml
│   ├── CVE-2018-3714.yaml
│   ├── CVE-2018-3760.yaml
│   ├── CVE-2018-5230.yaml
│   ├── CVE-2018-7490.yaml
│   ├── CVE-2019-1010287.yaml
│   ├── CVE-2019-10475.yaml
│   ├── CVE-2019-11043.yaml
│   ├── CVE-2019-11248.yaml
│   ├── CVE-2019-11510.yaml
│   ├── CVE-2019-11580.yaml
│   ├── CVE-2019-12314.yaml
│   ├── CVE-2019-12461.yaml
│   ├── CVE-2019-12593.yaml
│   ├── CVE-2019-14322.yaml
│   ├── CVE-2019-14696.yaml
│   ├── CVE-2019-14974.yaml
│   ├── CVE-2019-15043.yaml
│   ├── CVE-2019-15107.yaml
│   ├── CVE-2019-15858.yaml
│   ├── CVE-2019-16278.yaml
│   ├── CVE-2019-1653.yaml
│   ├── CVE-2019-16662.yaml
│   ├── CVE-2019-16759-1.yaml
│   ├── CVE-2019-16759.yaml
│   ├── CVE-2019-16920.yaml
│   ├── CVE-2019-17382.yaml
│   ├── CVE-2019-17558.yaml
│   ├── CVE-2019-18394.yaml
│   ├── CVE-2019-19368.yaml
│   ├── CVE-2019-19781.yaml
│   ├── CVE-2019-19908.yaml
│   ├── CVE-2019-19985.yaml
│   ├── CVE-2019-20141.yaml
│   ├── CVE-2019-2588.yaml
│   ├── CVE-2019-2725.yaml
│   ├── CVE-2019-3396.yaml
│   ├── CVE-2019-3402.yaml
│   ├── CVE-2019-3799.yaml
│   ├── CVE-2019-5418.yaml
│   ├── CVE-2019-6112.yaml
│   ├── CVE-2019-6340.yaml
│   ├── CVE-2019-6715.yaml
│   ├── CVE-2019-7256.yaml
│   ├── CVE-2019-7609.yaml
│   ├── CVE-2019-8442.yaml
│   ├── CVE-2019-8449.yaml
│   ├── CVE-2019-8451.yaml
│   ├── CVE-2019-8903.yaml
│   ├── CVE-2019-8982.yaml
│   ├── CVE-2019-9670.yaml
│   ├── CVE-2019-9733.yaml
│   ├── CVE-2019-9978.yaml
│   ├── CVE-2020-0618.yaml
│   ├── CVE-2020-10199.yaml
│   ├── CVE-2020-10204.yaml
│   ├── CVE-2020-11034.yaml
│   ├── CVE-2020-1147.yaml
│   ├── CVE-2020-12116.yaml
│   ├── CVE-2020-12720.yaml
│   ├── CVE-2020-13167.yaml
│   ├── CVE-2020-13942.yaml
│   ├── CVE-2020-14179.yaml
│   ├── CVE-2020-14181.yaml
│   ├── CVE-2020-14815.yaml
│   ├── CVE-2020-14864.yaml
│   ├── CVE-2020-14882.yaml
│   ├── CVE-2020-15129.yaml
│   ├── CVE-2020-15505.yaml
│   ├── CVE-2020-15920.yaml
│   ├── CVE-2020-16139.yaml
│   ├── CVE-2020-16846.yaml
│   ├── CVE-2020-16952.yaml
│   ├── CVE-2020-17505.yaml
│   ├── CVE-2020-17506.yaml
│   ├── CVE-2020-2096.yaml
│   ├── CVE-2020-2140.yaml
│   ├── CVE-2020-24223.yaml
│   ├── CVE-2020-24312.yaml
│   ├── CVE-2020-2551.yaml
│   ├── CVE-2020-25540.yaml
│   ├── CVE-2020-26214.yaml
│   ├── CVE-2020-3187.yaml
│   ├── CVE-2020-3452.yaml
│   ├── CVE-2020-5284.yaml
│   ├── CVE-2020-5405.yaml
│   ├── CVE-2020-5410.yaml
│   ├── CVE-2020-5412.yaml
│   ├── CVE-2020-5776.yaml
│   ├── CVE-2020-5777.yaml
│   ├── CVE-2020-5902.yaml
│   ├── CVE-2020-6287.yaml
│   ├── CVE-2020-7209.yaml
│   ├── CVE-2020-7961.yaml
│   ├── CVE-2020-8091.yaml
│   ├── CVE-2020-8115.yaml
│   ├── CVE-2020-8163.yaml
│   ├── CVE-2020-8191.yaml
│   ├── CVE-2020-8193.yaml
│   ├── CVE-2020-8194.yaml
│   ├── CVE-2020-8209.yaml
│   ├── CVE-2020-8512.yaml
│   ├── CVE-2020-8982.yaml
│   ├── CVE-2020-9047.yaml
│   ├── CVE-2020-9344.yaml
│   ├── CVE-2020-9484.yaml
│   ├── CVE-2020-9496.yaml
│   └── CVE-2020-9757.yaml
├── default-credentials
│   ├── grafana-default-credential.yaml
│   ├── rabbitmq-default-admin.yaml
│   └── tomcat-manager-default.yaml
├── dns
│   ├── azure-takeover-detection.yaml
│   ├── cname-service-detector.yaml
│   ├── dead-host-with-cname.yaml
│   ├── mx-service-detector.yaml
│   ├── servfail-refused-hosts.yaml
│   └── spoofable-spf-records-ptr.yaml
├── files
│   ├── apc-info.yaml
│   ├── cgi-test-page.yaml
│   ├── dir-listing.yaml
│   ├── docker-registry.yaml
│   ├── domcfg-page.yaml
│   ├── druid-monitor.yaml
│   ├── drupal-install.yaml
│   ├── ds_store.yaml
│   ├── elasticsearch.yaml
│   ├── elmah-log-file.yaml
│   ├── error-logs.yaml
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
│   ├── magento-config.yaml
│   ├── ntlm-directories.yaml
│   ├── php-errors.yaml
│   ├── phpinfo.yaml
│   ├── public-tomcat-instance.yaml
│   ├── robots.txt.yaml
│   ├── security.txt.yaml
│   ├── server-status-localhost.yaml
│   ├── shell-history.yaml
│   ├── sql-dump.yaml
│   ├── telerik-dialoghandler-detect.yaml
│   ├── telerik-fileupload-detect.yaml
│   ├── tomcat-scripts.yaml
│   ├── wadl-files.yaml
│   ├── web-config.yaml
│   ├── wordpress-db-backup.yaml
│   ├── wordpress-debug-log.yaml
│   ├── wordpress-directory-listing.yaml
│   ├── wordpress-emergency-script.yaml
│   ├── wordpress-installer-log.yaml
│   ├── wordpress-tmm-db-migrate.yaml
│   ├── wordpress-user-enumeration.yaml
│   ├── wp-xmlrpc.yaml
│   ├── wsdl-detect.yaml
│   └── zip-backup-files.yaml
├── fuzzing
│   ├── arbitrary-file-read.yaml
│   ├── basic-auth-bruteforce.yaml
│   ├── directory-traversal.yaml
│   ├── generic-lfi-fuzzing.yaml
│   ├── iis-shortname.yaml
│   └── wp-plugin-scan.yaml
├── generic-detections
│   ├── basic-xss-prober.yaml
│   ├── general-tokens.yaml
│   └── top-15-xss.yaml
├── panels
│   ├── adminer-panel.yaml
│   ├── aims-password-mgmt-client.yaml
│   ├── atlassian-crowd-panel.yaml
│   ├── cisco-asa-panel.yaml
│   ├── citrix-adc-gateway-detect.yaml
│   ├── compal.yaml
│   ├── crxde.yaml
│   ├── docker-api.yaml
│   ├── fortinet-fortigate-panel.yaml
│   ├── globalprotect-panel.yaml
│   ├── go-anywhere-client.yaml
│   ├── grafana-detect.yaml
│   ├── iomega-lenovo-emc-shared-nas-detect.yaml
│   ├── jenkins-asyncpeople.yaml
│   ├── jmx-console.yaml
│   ├── kubernetes-pods.yaml
│   ├── manage-engine-admanager-panel.yaml
│   ├── mobileiron-login.yaml
│   ├── netscaler-gateway.yaml
│   ├── network-camera-detect.yaml
│   ├── parallels-html-client.yaml
│   ├── phpmyadmin-panel.yaml
│   ├── polycom-admin-detect.yaml
│   ├── pulse-secure-panel.yaml
│   ├── rabbitmq-dashboard.yaml
│   ├── sap-netweaver-detect.yaml
│   ├── sap-recon-detect.yaml
│   ├── sonarqube-login.yaml
│   ├── sonicwall-management-panel.yaml
│   ├── sonicwall-sslvpn-panel.yaml
│   ├── sophos-fw-version-detect.yaml
│   ├── supervpn-panel.yaml
│   ├── swagger-panel.yaml
│   ├── tikiwiki-cms.yaml
│   ├── traefik-dashboard.yaml
│   ├── virtual-ema-detect.yaml
│   ├── weave-scope-dashboard-detect.yaml
│   ├── webeditors.yaml
│   ├── workspace-one-uem.yaml
│   └── workspaceone-uem-airwatch-dashboard-detect.yaml
├── payloads
│   ├── CVE-2020-5776.csv
│   └── CVE-2020-6287.xml
├── security-misconfiguration
│   ├── apache-tomcat-snoop.yaml
│   ├── basic-cors-flash.yaml
│   ├── basic-cors.yaml
│   ├── django-debug-detect.yaml
│   ├── drupal-user-enum-ajax.yaml
│   ├── drupal-user-enum-redirect.yaml
│   ├── front-page-misconfig.yaml
│   ├── jira-service-desk-signup.yaml
│   ├── jira-unauthenticated-dashboards.yaml
│   ├── jira-unauthenticated-popular-filters.yaml
│   ├── jira-unauthenticated-projects.yaml
│   ├── jira-unauthenticated-user-picker.yaml
│   ├── jupyter-ipython-unauth.yaml
│   ├── larvel-debug.yaml
│   ├── manage-engine-ad-search.yaml
│   ├── missing-csp.yaml
│   ├── missing-hsts.yaml
│   ├── missing-x-frame-options.yaml
│   ├── put-method-enabled.yaml
│   ├── rack-mini-profiler.yaml
│   ├── salesforce-aura-misconfig.yaml
│   ├── sidekiq-dashboard.yaml
│   ├── springboot-detect.yaml
│   ├── unauthenticated-airflow.yaml
│   ├── unauthenticated-jenkin-dashboard.yaml
│   ├── wamp-xdebug-detect.yaml
│   ├── wordpress-accessible-wpconfig.yaml
│   └── zenphoto-installation-sensitive-info.yaml
├── subdomain-takeover
│   ├── detect-all-takeovers.yaml
│   └── s3-subtakeover.yaml
├── technologies
│   ├── apache-detect.yaml
│   ├── artica-web-proxy-detect.yaml
│   ├── basic-auth-detection.yaml
│   ├── bigip-config-utility-detect.yaml
│   ├── citrix-vpn-detect.yaml
│   ├── clockwork-php-page.yaml
│   ├── couchdb-detect.yaml
│   ├── favicon-detection.yaml
│   ├── github-enterprise-detect.yaml
│   ├── gitlab-detect.yaml
│   ├── google-storage.yaml
│   ├── graphql.yaml
│   ├── home-assistant.yaml
│   ├── jaspersoft-detect.yaml
│   ├── jira-detect.yaml
│   ├── kibana-detect.yaml
│   ├── kong-detect.yaml
│   ├── liferay-portal-detect.yaml
│   ├── linkerd-badrule-detect.yaml
│   ├── linkerd-ssrf-detect.yaml
│   ├── lotus-domino-version.yaml
│   ├── magmi-detect.yaml
│   ├── netsweeper-webadmin-detect.yaml
│   ├── nifi-detech.yaml
│   ├── oidc-detect.yaml
│   ├── pi-hole-detect.yaml
│   ├── prometheus-exporter-detect.yaml
│   ├── prometheus-exposed-panel.yaml
│   ├── s3-detect.yaml
│   ├── sap-netweaver-as-java-detect.yaml
│   ├── sap-netweaver-detect.yaml
│   ├── shiro-detect.yaml
│   ├── sql-server-reporting.yaml
│   ├── tech-detect.yaml
│   ├── tomcat-detect.yaml
│   ├── waf-detect.yaml
│   ├── weblogic-detect.yaml
│   └── werkzeug-debugger-detect.yaml
├── tokens
│   ├── amazon-mws-auth-token-value.yaml
│   ├── aws-access-key-value.yaml
│   ├── credentials-disclosure.yaml
│   ├── fcm-server-key.yaml
│   ├── google-api-key.yaml
│   ├── http-username-password.yaml
│   ├── mailchimp-api-key.yaml
│   └── slack-access-token.yaml
├── vulnerabilities
│   ├── bullwark-momentum-series-directory-traversal.yaml
│   ├── cached-aem-pages.yaml
│   ├── couchdb-adminparty.yaml
│   ├── crlf-injection.yaml
│   ├── discourse-xss.yaml
│   ├── eclipse-help-system-xss.yaml
│   ├── git-config-nginxoffbyslash.yaml
│   ├── ibm-infoprint-directory-traversal.yaml
│   ├── microstrategy-ssrf.yaml
│   ├── mida-eframework-xss.yaml
│   ├── moodle-filter-jmol-lfi.yaml
│   ├── moodle-filter-jmol-xss.yaml
│   ├── nginx-module-vts-xss.yaml
│   ├── nuuo-nvrmini2-rce.yaml
│   ├── open-redirect.yaml
│   ├── oracle-ebs-bispgraph-file-access.yaml
│   ├── pdf-signer-ssti-to-rce.yaml
│   ├── rce-shellshock-user-agent.yaml
│   ├── rce-via-java-deserialization.yaml
│   ├── rconfig-rce.yaml
│   ├── sassy-social-share.yaml
│   ├── sick-beard-xss.yaml
│   ├── springboot-actuators-jolokia-xxe.yaml
│   ├── springboot-h2-db-rce.yaml
│   ├── symantec-messaging-gateway.yaml
│   ├── symfony-debugmode.yaml
│   ├── tikiwiki-reflected-xss.yaml
│   ├── tomcat-manager-pathnormalization.yaml
│   ├── twig-php-ssti.yaml
│   ├── vmware-vcenter-lfi.yaml
│   ├── vpms-auth-bypass.yaml
│   ├── w3c-total-cache-ssrf.yaml
│   ├── wems-manager-xss.yaml
│   ├── wordpress-duplicator-path-traversal.yaml
│   ├── wordpress-emails-verification-for-woocommerce.yaml
│   ├── wordpress-social-metrics-tracker.yaml
│   ├── wordpress-wordfence-xss.yaml
│   ├── wordpress-wpcourses-info-disclosure.yaml
│   └── zms-auth-bypass.yaml
├── wordlists
│   └── wp-plugins.txt
└── workflows
    ├── artica-web-proxy-workflow.yaml
    ├── basic-auth-workflow.yaml
    ├── bigip-workflow.yaml
    ├── cisco-asa-workflow.yaml
    ├── grafana-workflow.yaml
    ├── jira-workflow.yaml
    ├── liferay-workflow.yaml
    ├── lotus-domino-workflow.yaml
    ├── magmi-workflow.yaml
    ├── mida-eframework-workflow.yaml
    ├── netsweeper-workflow.yaml
    ├── rabbitmq-workflow.yaml
    ├── sap-netweaver-workflow.yaml
    ├── springboot-workflow.yaml
    ├── vbulletin-workflow.yaml
    └── wordpress-workflow.yaml
```

</details>

**15 directories, 377 files**.

Please navigate to https://nuclei.projectdiscovery.io for detailed documentation to build new and your own custom templates and many example templates for easy understanding. 

------
**Notes:** 
1. Use YAMLlint (e.g. [yamllint](http://www.yamllint.com/)) to validate new templates when sending pull requests.
2. Use YAML Formatter (e.g. [jsonformatter](https://jsonformatter.org/yaml-formatter)) to format new templates when sending pull requests.

Thanks again for your contribution and keeping the community vibrant. :heart:
