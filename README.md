# Some scripts I use for OPNsense

Currently this repo contains two scripts, both for automating the renewal of X509 certificates.<br>
I use Let's Encrypt certificates, which are automatically renewed on a central server.<br>
From there they are copied over to the servers which need them, among which the OPNsense firewall.<br>
Once they arrive there, they need to be imported, configured and activated.

## opnsense-import-ssl.php
This script originates from Sheridan (https://github.com/sheridans/opnsense-import-ssl).<br>
It imports the certificate, and configures and activates it for the Web GUI of OPNsense.<br>
It works beautifully.  (thanks Sheridan!)

## opnsense-update-capt-portal-ssl.php
This script was authored by me.<br>
On OPNsense I have a Captive Portal running in front of the Wireguard instance.<br>
It redirects incoming traffic to the Captive Portal, which happens to use the same URL (CN) as the Web GUI.<br>
However, when the certificate of the Web GUI is renewed, the Captive Portal keeps using the old certificate.<br>
To solve this I wrote the script ```opnsense-update-capt-portal-ssl.php``` which compares the certificates of the Web GUI and the Captive Portal, and updates/activates the configuration of the Captive Portal zone for Wireguard when they happen to be different.

Arguments:
```
--key     <key of the user>
--secret  <secret of the user>
--url     <fully qualified host name, including https:// and a trailing slash>
--portal  <the description of the Captive Portal, as is present in the 'description' field of the zone configuration>
```
