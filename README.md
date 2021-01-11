
# Nuclei Templates

[![License](https://img.shields.io/badge/license-MIT-_red.svg)](https://opensource.org/licenses/MIT)
[![GitHub Release](https://img.shields.io/github/release/projectdiscovery/nuclei-templates)](https://github.com/projectdiscovery/nuclei-templates/releases)
[![contributions welcome](https://img.shields.io/badge/contributions-welcome-brightgreen.svg?style=flat)](https://github.com/projectdiscovery/nuclei-templates/issues)
[![Follow on Twitter](https://img.shields.io/twitter/follow/pdnuclei.svg?logo=twitter)](https://twitter.com/pdnuclei)
[![Chat on Discord](https://img.shields.io/discord/695645237418131507.svg?logo=discord)](https://discord.gg/KECAGdH)

Templates are the core of [nuclei scanner](https://github.com/projectdiscovery/nuclei) which power the actual scanning engine. This repository stores and houses various templates for the scanner provided by our team as well as contributed by the community. We hope that you also contribute by sending templates via **pull requests** or [Github issue](https://github.com/projectdiscovery/nuclei-templates/issues/new?assignees=&labels=&template=submit-template.md&title=%5Bnuclei-template%5D+) and grow the list.

An overview of the nuclei template directory including number of templates and HTTP request associated with each directory. 

### Nuclei templates `v7.3.7` overview

| Templates       | Counts                          | Templates        | Counts                         |
| --------------- | ------------------------------- | ---------------- | ------------------------------ |
| cves            | 154            | default-logins   | 8 |
| dns             | 6               | exposed-panels   | 72   |
| exposed-tokens  | 9  | exposures        | 41      |
| fuzzing         | 6           | helpers          | 3        |
| miscellaneous   | 14     | misconfiguration | 37 |
| takeovers       | 1         | technologies     | 44     |
| vulnerabilities | 62 | workflows        | 17        |


### Nuclei templates `v7.3.7` tree overview

<details>
<summary> Nuclei templates </summary>

```
├── LICENSE.md
├── README.md
├── cves
│   ├── 2005
│   │   └── CVE-2005-2428.yaml
│   ├── 2008
│   │   └── CVE-2008-2398.yaml
│   ├── 2013
│   │   └── CVE-2013-2251.yaml
│   ├── 2014
│   │   └── CVE-2014-6271.yaml
│   ├── 2017
│   │   ├── CVE-2017-10075.yaml
│   │   ├── CVE-2017-11444.yaml
│   │   ├── CVE-2017-12637.yaml
│   │   ├── CVE-2017-14537.yaml
│   │   ├── CVE-2017-14849.yaml
│   │   ├── CVE-2017-5638.yaml
│   │   ├── CVE-2017-7391.yaml
│   │   ├── CVE-2017-7615.yaml
│   │   ├── CVE-2017-9506.yaml
│   │   └── CVE-2017-9841.yaml
│   ├── 2018
│   │   ├── CVE-2018-0296.yaml
│   │   ├── CVE-2018-1000129.yaml
│   │   ├── CVE-2018-11409.yaml
│   │   ├── CVE-2018-11759.yaml
│   │   ├── CVE-2018-1247.yaml
│   │   ├── CVE-2018-1271.yaml
│   │   ├── CVE-2018-1273.yaml
│   │   ├── CVE-2018-13379.yaml
│   │   ├── CVE-2018-13380.yaml
│   │   ├── CVE-2018-14728.yaml
│   │   ├── CVE-2018-16341.yaml
│   │   ├── CVE-2018-16763.yaml
│   │   ├── CVE-2018-17431.yaml
│   │   ├── CVE-2018-18069.yaml
│   │   ├── CVE-2018-19386.yaml
│   │   ├── CVE-2018-19439.yaml
│   │   ├── CVE-2018-20824.yaml
│   │   ├── CVE-2018-2791.yaml
│   │   ├── CVE-2018-3714.yaml
│   │   ├── CVE-2018-3760.yaml
│   │   ├── CVE-2018-5230.yaml
│   │   ├── CVE-2018-7251.yaml
│   │   ├── CVE-2018-7490.yaml
│   │   └── CVE-2018-8006.yaml
│   ├── 2019
│   │   ├── CVE-2019-10092.yaml
│   │   ├── CVE-2019-1010287.yaml
│   │   ├── CVE-2019-10475.yaml
│   │   ├── CVE-2019-11248.yaml
│   │   ├── CVE-2019-11510.yaml
│   │   ├── CVE-2019-11580.yaml
│   │   ├── CVE-2019-11581.yaml
│   │   ├── CVE-2019-11869.yaml
│   │   ├── CVE-2019-12314.yaml
│   │   ├── CVE-2019-12461.yaml
│   │   ├── CVE-2019-12593.yaml
│   │   ├── CVE-2019-12725.yaml
│   │   ├── CVE-2019-14223.yaml
│   │   ├── CVE-2019-14322.yaml
│   │   ├── CVE-2019-14696.yaml
│   │   ├── CVE-2019-14974.yaml
│   │   ├── CVE-2019-15043.yaml
│   │   ├── CVE-2019-15107.yaml
│   │   ├── CVE-2019-15858.yaml
│   │   ├── CVE-2019-16278.yaml
│   │   ├── CVE-2019-1653.yaml
│   │   ├── CVE-2019-16662.yaml
│   │   ├── CVE-2019-16759-1.yaml
│   │   ├── CVE-2019-16759.yaml
│   │   ├── CVE-2019-16920.yaml
│   │   ├── CVE-2019-17382.yaml
│   │   ├── CVE-2019-17558.yaml
│   │   ├── CVE-2019-18394.yaml
│   │   ├── CVE-2019-19368.yaml
│   │   ├── CVE-2019-19781.yaml
│   │   ├── CVE-2019-19908.yaml
│   │   ├── CVE-2019-19985.yaml
│   │   ├── CVE-2019-20141.yaml
│   │   ├── CVE-2019-2588.yaml
│   │   ├── CVE-2019-2725.yaml
│   │   ├── CVE-2019-3396.yaml
│   │   ├── CVE-2019-3402.yaml
│   │   ├── CVE-2019-3799.yaml
│   │   ├── CVE-2019-5418.yaml
│   │   ├── CVE-2019-6112.yaml
│   │   ├── CVE-2019-6340.yaml
│   │   ├── CVE-2019-6715.yaml
│   │   ├── CVE-2019-7219.yaml
│   │   ├── CVE-2019-7256.yaml
│   │   ├── CVE-2019-7609.yaml
│   │   ├── CVE-2019-8442.yaml
│   │   ├── CVE-2019-8449.yaml
│   │   ├── CVE-2019-8451.yaml
│   │   ├── CVE-2019-8903.yaml
│   │   ├── CVE-2019-8982.yaml
│   │   ├── CVE-2019-9670.yaml
│   │   ├── CVE-2019-9733.yaml
│   │   ├── CVE-2019-9955.yaml
│   │   └── CVE-2019-9978.yaml
│   └── 2020
│       ├── CVE-2020-0618.yaml
│       ├── CVE-2020-10148.yaml
│       ├── CVE-2020-10199.yaml
│       ├── CVE-2020-10204.yaml
│       ├── CVE-2020-11034.yaml
│       ├── CVE-2020-1147.yaml
│       ├── CVE-2020-11738.yaml
│       ├── CVE-2020-12116.yaml
│       ├── CVE-2020-12720.yaml
│       ├── CVE-2020-13167.yaml
│       ├── CVE-2020-13942.yaml
│       ├── CVE-2020-14179.yaml
│       ├── CVE-2020-14181.yaml
│       ├── CVE-2020-14864.yaml
│       ├── CVE-2020-14882.yaml
│       ├── CVE-2020-15129.yaml
│       ├── CVE-2020-15505.yaml
│       ├── CVE-2020-15920.yaml
│       ├── CVE-2020-16139.yaml
│       ├── CVE-2020-16846.yaml
│       ├── CVE-2020-16952.yaml
│       ├── CVE-2020-17505.yaml
│       ├── CVE-2020-17506.yaml
│       ├── CVE-2020-17518.yaml
│       ├── CVE-2020-17519.yaml
│       ├── CVE-2020-1943.yaml
│       ├── CVE-2020-2096.yaml
│       ├── CVE-2020-2140.yaml
│       ├── CVE-2020-23972.yaml
│       ├── CVE-2020-24223.yaml
│       ├── CVE-2020-24312.yaml
│       ├── CVE-2020-2551.yaml
│       ├── CVE-2020-25540.yaml
│       ├── CVE-2020-26214.yaml
│       ├── CVE-2020-3187.yaml
│       ├── CVE-2020-3452.yaml
│       ├── CVE-2020-4463.yaml
│       ├── CVE-2020-5284.yaml
│       ├── CVE-2020-5405.yaml
│       ├── CVE-2020-5410.yaml
│       ├── CVE-2020-5412.yaml
│       ├── CVE-2020-5776.yaml
│       ├── CVE-2020-5777.yaml
│       ├── CVE-2020-5902.yaml
│       ├── CVE-2020-6287.yaml
│       ├── CVE-2020-7209.yaml
│       ├── CVE-2020-7318.yaml
│       ├── CVE-2020-7961.yaml
│       ├── CVE-2020-8091.yaml
│       ├── CVE-2020-8115.yaml
│       ├── CVE-2020-8163.yaml
│       ├── CVE-2020-8191.yaml
│       ├── CVE-2020-8193.yaml
│       ├── CVE-2020-8194.yaml
│       ├── CVE-2020-8209.yaml
│       ├── CVE-2020-8512.yaml
│       ├── CVE-2020-8982.yaml
│       ├── CVE-2020-9047.yaml
│       ├── CVE-2020-9344.yaml
│       ├── CVE-2020-9484.yaml
│       ├── CVE-2020-9496.yaml
│       └── CVE-2020-9757.yaml
├── default-logins
│   ├── activemq
│   │   └── activemq-default-login.yaml
│   ├── ambari
│   │   └── ambari-default-credentials.yaml
│   ├── apache
│   │   └── tomcat-manager-default.yaml
│   ├── grafana
│   │   └── grafana-default-credential.yaml
│   ├── ofbiz
│   │   └── ofbiz-default-credentials.yaml
│   ├── rabbitmq
│   │   └── rabbitmq-default-admin.yaml
│   ├── solarwinds
│   │   └── solarwinds-default-admin.yaml
│   └── zabbix
│       └── zabbix-default-credentials.yaml
├── dns
│   ├── azure-takeover-detection.yaml
│   ├── cname-service-detector.yaml
│   ├── dead-host-with-cname.yaml
│   ├── mx-service-detector.yaml
│   ├── servfail-refused-hosts.yaml
│   └── spoofable-spf-records-ptr.yaml
├── exposed-panels
│   ├── active-admin-exposure.yaml
│   ├── activemq-panel.yaml
│   ├── adminer-panel.yaml
│   ├── aims-password-mgmt-client.yaml
│   ├── airflow-exposure.yaml
│   ├── ambari-exposure.yaml
│   ├── ansible-tower-exposure.yaml
│   ├── atlassian-crowd-panel.yaml
│   ├── cisco-asa-panel.yaml
│   ├── citrix-adc-gateway-detect.yaml
│   ├── citrix-vpn-detect.yaml
│   ├── compal-panel.yaml
│   ├── couchdb-exposure.yaml
│   ├── couchdb-fauxton.yaml
│   ├── crxde.yaml
│   ├── django-admin-panel.yaml
│   ├── druid-console-exposure.yaml
│   ├── exposed-pagespeed-global-admin.yaml
│   ├── exposed-webalizer.yaml
│   ├── flink-exposure.yaml
│   ├── fortinet-fortigate-panel.yaml
│   ├── fortiweb-panel.yaml
│   ├── github-enterprise-detect.yaml
│   ├── gitlab-detect.yaml
│   ├── globalprotect-panel.yaml
│   ├── go-anywhere-client.yaml
│   ├── grafana-detect.yaml
│   ├── hadoop-exposure.yaml
│   ├── identityguard-selfservice-entrust.yaml
│   ├── iomega-lenovo-emc-shared-nas-detect.yaml
│   ├── jira-detect.yaml
│   ├── jmx-console.yaml
│   ├── kafka-connect-ui.yaml
│   ├── kafka-monitoring.yaml
│   ├── kafka-topics-ui.yaml
│   ├── kubernetes-dashboard.yaml
│   ├── manage-engine-admanager-panel.yaml
│   ├── mobileiron-login.yaml
│   ├── netscaler-gateway.yaml
│   ├── network-camera-detect.yaml
│   ├── oipm-detect.yaml
│   ├── parallels-html-client.yaml
│   ├── phpmyadmin-panel.yaml
│   ├── polycom-admin-detect.yaml
│   ├── public-tomcat-instance.yaml
│   ├── pulse-secure-panel.yaml
│   ├── rabbitmq-dashboard.yaml
│   ├── rocketmq-console-exposure.yaml
│   ├── rsa-self-service.yaml
│   ├── sap-hana-xsengine-panel.yaml
│   ├── sap-netweaver-detect.yaml
│   ├── sap-recon-detect.yaml
│   ├── selenoid-ui-exposure.yaml
│   ├── setup-page-exposure.yaml
│   ├── solarwinds-orion.yaml
│   ├── solr-exposure.yaml
│   ├── sonarqube-login.yaml
│   ├── sonicwall-management-panel.yaml
│   ├── sonicwall-sslvpn-panel.yaml
│   ├── sophos-fw-version-detect.yaml
│   ├── supervpn-panel.yaml
│   ├── tikiwiki-cms.yaml
│   ├── tomcat-manager-pathnormalization.yaml
│   ├── traefik-dashboard.yaml
│   ├── virtual-ema-detect.yaml
│   ├── weave-scope-dashboard-detect.yaml
│   ├── webeditors.yaml
│   ├── webmin-panel.yaml
│   ├── workspace-one-uem.yaml
│   ├── workspaceone-uem-airwatch-dashboard-detect.yaml
│   ├── yarn-manager-exposure.yaml
│   └── zipkin-exposure.yaml
├── exposed-tokens
│   ├── aws
│   │   ├── amazon-mws-auth-token-value.yaml
│   │   └── aws-access-key-value.yaml
│   ├── generic
│   │   ├── credentials-disclosure.yaml
│   │   ├── general-tokens.yaml
│   │   └── http-username-password.yaml
│   ├── google
│   │   ├── fcm-server-key.yaml
│   │   └── google-api-key.yaml
│   ├── mailchimp
│   │   └── mailchimp-api-key.yaml
│   └── slack
│       └── slack-access-token.yaml
├── exposures
│   ├── apis
│   │   ├── swagger-api.yaml
│   │   ├── wadl-api.yaml
│   │   └── wsdl-api.yaml
│   ├── backups
│   │   ├── sql-dump.yaml
│   │   └── zip-backup-files.yaml
│   ├── configs
│   │   ├── airflow-configuration-exposure.yaml
│   │   ├── amazon-docker-config-disclosure.yaml
│   │   ├── ansible-config-disclosure.yaml
│   │   ├── composer-config.yaml
│   │   ├── exposed-alps-spring.yaml
│   │   ├── exposed-svn.yaml
│   │   ├── git-config-nginxoffbyslash.yaml
│   │   ├── git-config.yaml
│   │   ├── htpasswd-detection.yaml
│   │   ├── laravel-env.yaml
│   │   ├── magento-config.yaml
│   │   ├── opcache-status-exposure.yaml
│   │   ├── owncloud-config.yaml
│   │   ├── package-json.yaml
│   │   ├── perl-status.yaml
│   │   ├── phpinfo.yaml
│   │   ├── rails-database-config.yaml
│   │   ├── redmine-db-config.yaml
│   │   ├── server-status-localhost.yaml
│   │   ├── syfmony-profiler.yaml
│   │   ├── symfony-database-config.yaml
│   │   ├── symfony-profiler.yaml
│   │   └── web-config.yaml
│   ├── files
│   │   ├── domcfg-page.yaml
│   │   ├── drupal-install.yaml
│   │   ├── ds_store.yaml
│   │   ├── exposed-alps-spring.yaml
│   │   ├── filezilla.yaml
│   │   ├── lazy-file.yaml
│   │   ├── server-private-keys.yaml
│   │   └── xprober-service.yaml
│   └── logs
│       ├── elmah-log-file.yaml
│       ├── error-logs.yaml
│       ├── rails-debug-mode.yaml
│       ├── struts-debug-mode.yaml
│       └── trace-axd-detect.yaml
├── fuzzing
│   ├── arbitrary-file-read.yaml
│   ├── basic-auth-bruteforce.yaml
│   ├── directory-traversal.yaml
│   ├── generic-lfi-fuzzing.yaml
│   ├── iis-shortname.yaml
│   └── wp-plugin-scan.yaml
├── helpers
│   ├── payloads
│   │   ├── CVE-2020-5776.csv
│   │   └── CVE-2020-6287.xml
│   └── wordlists
│       └── wp-plugins.txt
├── miscellaneous
│   ├── basic-cors-flash.yaml
│   ├── dir-listing.yaml
│   ├── htaccess-config.yaml
│   ├── missing-csp.yaml
│   ├── missing-hsts.yaml
│   ├── missing-x-frame-options.yaml
│   ├── ntlm-directories.yaml
│   ├── old-copyright.yaml
│   ├── robots.txt.yaml
│   ├── security.txt.yaml
│   ├── trace-method.yaml
│   ├── unencrypted-bigip-ltm-cookie.yaml
│   ├── wp-xmlrpc.yaml
│   └── xml-schema-detect.yaml
├── misconfiguration
│   ├── aem-groovyconsole.yaml
│   ├── airflow-api-exposure.yaml
│   ├── apache-tomcat-snoop.yaml
│   ├── apc-info.yaml
│   ├── aspx-debug-mode.yaml
│   ├── aws-redirect.yaml
│   ├── cgi-test-page.yaml
│   ├── django-debug-detect.yaml
│   ├── docker-api.yaml
│   ├── docker-registry.yaml
│   ├── druid-monitor.yaml
│   ├── drupal-user-enum-ajax.yaml
│   ├── drupal-user-enum-redirect.yaml
│   ├── elasticsearch.yaml
│   ├── exposed-kibana.yaml
│   ├── exposed-service-now.yaml
│   ├── front-page-misconfig.yaml
│   ├── hadoop-unauth.yaml
│   ├── jkstatus-manager.yaml
│   ├── jupyter-ipython-unauth.yaml
│   ├── kubernetes-pods.yaml
│   ├── larvel-debug.yaml
│   ├── linkerd-ssrf-detect.yaml
│   ├── manage-engine-ad-search.yaml
│   ├── php-errors.yaml
│   ├── put-method-enabled.yaml
│   ├── rack-mini-profiler.yaml
│   ├── salesforce-aura-misconfig.yaml
│   ├── shell-history.yaml
│   ├── sidekiq-dashboard.yaml
│   ├── springboot-detect.yaml
│   ├── symfony-debugmode.yaml
│   ├── tomcat-scripts.yaml
│   ├── unauthenticated-airflow.yaml
│   ├── unauthenticated-nacos-access.yaml
│   ├── wamp-xdebug-detect.yaml
│   └── zenphoto-installation-sensitive-info.yaml
├── takeovers
│   └── subdomain-takeover.yaml
├── technologies
│   ├── apache-detect.yaml
│   ├── artica-web-proxy-detect.yaml
│   ├── basic-auth-detection.yaml
│   ├── bigip-config-utility-detect.yaml
│   ├── cacti-detect.yaml
│   ├── clockwork-php-page.yaml
│   ├── couchdb-detect.yaml
│   ├── favicon-detection.yaml
│   ├── firebase-detect.yaml
│   ├── google-storage.yaml
│   ├── graphql.yaml
│   ├── graylog-api-browser.yaml
│   ├── home-assistant.yaml
│   ├── jaspersoft-detect.yaml
│   ├── jolokia.yaml
│   ├── kibana-detect.yaml
│   ├── kong-detect.yaml
│   ├── liferay-portal-detect.yaml
│   ├── linkerd-badrule-detect.yaml
│   ├── lotus-domino-version.yaml
│   ├── magmi-detect.yaml
│   ├── mrtg-detect.yaml
│   ├── netsweeper-webadmin-detect.yaml
│   ├── nifi-detech.yaml
│   ├── oidc-detect.yaml
│   ├── pi-hole-detect.yaml
│   ├── prometheus-exporter-detect.yaml
│   ├── prometheus-exposed-panel.yaml
│   ├── prtg-detect.yaml
│   ├── redmine-cli-detect.yaml
│   ├── s3-detect.yaml
│   ├── sap-netweaver-as-java-detect.yaml
│   ├── sap-netweaver-detect.yaml
│   ├── shiro-detect.yaml
│   ├── sql-server-reporting.yaml
│   ├── tech-detect.yaml
│   ├── telerik-dialoghandler-detect.yaml
│   ├── telerik-fileupload-detect.yaml
│   ├── terraform-detect.yaml
│   ├── tomcat-detect.yaml
│   ├── tor-socks-proxy.yaml
│   ├── waf-detect.yaml
│   ├── weblogic-detect.yaml
│   └── werkzeug-debugger-detect.yaml
├── vulnerabilities
│   ├── generic
│   │   ├── basic-cors.yaml
│   │   ├── basic-xss-prober.yaml
│   │   ├── crlf-injection.yaml
│   │   ├── top-xss-params.yaml
│   │   └── url-redirect.yaml
│   ├── ibm
│   │   ├── eclipse-help-system-xss.yaml
│   │   └── ibm-infoprint-directory-traversal.yaml
│   ├── jenkins
│   │   ├── jenkins-asyncpeople.yaml
│   │   ├── jenkins-stack-trace.yaml
│   │   └── unauthenticated-jenkin-dashboard.yaml
│   ├── jira
│   │   ├── jira-service-desk-signup.yaml
│   │   ├── jira-unauthenticated-dashboards.yaml
│   │   ├── jira-unauthenticated-popular-filters.yaml
│   │   ├── jira-unauthenticated-projects.yaml
│   │   └── jira-unauthenticated-user-picker.yaml
│   ├── moodle
│   │   ├── moodle-filter-jmol-lfi.yaml
│   │   └── moodle-filter-jmol-xss.yaml
│   ├── oracle
│   │   └── oracle-ebs-bispgraph-file-access.yaml
│   ├── other
│   │   ├── acme-xss.yaml
│   │   ├── aspnuke-openredirect.yaml
│   │   ├── bullwark-momentum-series-directory-traversal.yaml
│   │   ├── cached-aem-pages.yaml
│   │   ├── couchdb-adminparty.yaml
│   │   ├── discourse-xss.yaml
│   │   ├── mcafee-epo-rce.yaml
│   │   ├── microstrategy-ssrf.yaml
│   │   ├── mida-eframework-xss.yaml
│   │   ├── nginx-module-vts-xss.yaml
│   │   ├── nuuo-nvrmini2-rce.yaml
│   │   ├── pdf-signer-ssti-to-rce.yaml
│   │   ├── rce-shellshock-user-agent.yaml
│   │   ├── rce-via-java-deserialization.yaml
│   │   ├── rconfig-rce.yaml
│   │   ├── sick-beard-xss.yaml
│   │   ├── symantec-messaging-gateway.yaml
│   │   ├── thinkific-redirect.yaml
│   │   ├── tikiwiki-reflected-xss.yaml
│   │   ├── twig-php-ssti.yaml
│   │   ├── vpms-auth-bypass.yaml
│   │   ├── wems-manager-xss.yaml
│   │   ├── yarn-resourcemanager-rce.yaml
│   │   └── zms-auth-bypass.yaml
│   ├── rails
│   │   └── rails6-xss.yaml
│   ├── springboot
│   │   ├── springboot-actuators-jolokia-xxe.yaml
│   │   └── springboot-h2-db-rce.yaml
│   ├── thinkific-redirect.yaml
│   ├── vmware
│   │   ├── vmware-vcenter-lfi-linux.yaml
│   │   └── vmware-vcenter-lfi.yaml
│   └── wordpress
│       ├── easy-wp-smtp-listing.yaml
│       ├── sassy-social-share.yaml
│       ├── w3c-total-cache-ssrf.yaml
│       ├── wordpress-accessible-wpconfig.yaml
│       ├── wordpress-db-backup.yaml
│       ├── wordpress-debug-log.yaml
│       ├── wordpress-directory-listing.yaml
│       ├── wordpress-emails-verification-for-woocommerce.yaml
│       ├── wordpress-emergency-script.yaml
│       ├── wordpress-installer-log.yaml
│       ├── wordpress-social-metrics-tracker.yaml
│       ├── wordpress-tmm-db-migrate.yaml
│       ├── wordpress-user-enumeration.yaml
│       ├── wordpress-wordfence-xss.yaml
│       └── wordpress-wpcourses-info-disclosure.yaml
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
    ├── solarwinds-orion-workflow.yaml
    ├── springboot-workflow.yaml
    ├── vbulletin-workflow.yaml
    └── wordpress-workflow.yaml
```

</details>

**53 directories, 477 files**.

Please navigate to https://nuclei.projectdiscovery.io for detailed documentation to build new and your own custom templates and many example templates for easy understanding. 

------
**Notes:** 
1. Use YAMLlint (e.g. [yamllint](http://www.yamllint.com/)) to validate new templates when sending pull requests.
2. Use YAML Formatter (e.g. [jsonformatter](https://jsonformatter.org/yaml-formatter)) to format new templates when sending pull requests.

Thanks again for your contribution and keeping the community vibrant. :heart:
