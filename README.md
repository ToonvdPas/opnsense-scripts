# Some scripts I use for OPNsense

Currently two scripts, both for automating the renewal of X509 certificates.<br>
I use Let's Encrypt certificates, and the are renewed on a central server.<br>
From there they are copied over to the servers which need them, among which the OPNsense firewall.<br>
Once there they need to be imported, configured and activated.

## opnsense-import-ssl.php
This script originates from Sheridan (https://github.com/sheridans/opnsense-import-ssl).<br>
It imports the certificate, and configures and activates it for the Web GUI.<br>
It works beautifully.  (thanks Sheridan!)

## opnsense-update-capt-portal-ssl.php
This script was authored by me.<br>
I have a Captive Portal running in front of the Wireguard instance.<br>
It redirects incoming traffic to the Captive Portal, which happens to use the same URL as the Web GUI, hence also the same certificate (CN).<br>
However, the Captive Portal keeps using the old certificate after it has been renewed for the Web GUI.<br>
The script ```opnsense-update-capt-portal-ssl.php``` compares the certificates of the Web GUI and the Captive Portal, and updates/activates the configuration of the Captive Portal zone for Wireguard.

Arguments:
```
--key     <key of the user>
--secret  <secret of the user>
--url     <fully qualified host name, including https:// and a trailing slash>
--portal  <the description of the Captive Portal, as is present in the ```description``` field of the zone configuration>
```
