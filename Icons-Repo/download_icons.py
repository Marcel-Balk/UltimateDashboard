#!/usr/bin/env python3
"""Download additional Simple Icons SVGs and update icons.json manifest."""

import json
import time
import urllib.request
import urllib.error
import os

ICONS_DIR = r"C:\Users\marcelbalk\Desktop\Cloude\Ultimate Dashboard\Github production\UltimateDashboard\Icons-Repo\icons"
MANIFEST_PATH = r"C:\Users\marcelbalk\Desktop\Cloude\Ultimate Dashboard\Github production\UltimateDashboard\Icons-Repo\icons.json"
SI_ICON_URL = "https://raw.githubusercontent.com/simple-icons/simple-icons/develop/icons/{slug}.svg"
SI_DATA_URL = "https://raw.githubusercontent.com/simple-icons/simple-icons/develop/data/simple-icons.json"

ADDITIONAL = [
    # More cloud & infrastructure
    ('Tencent Cloud', 'tencentcloud', 'cloud'),
    ('Alibaba', 'alibaba', 'cloud'),
    ('Naver Cloud', 'naver', 'cloud'),
    ('Ionos', 'ionos', 'cloud'),
    ('Hetzner', 'hetzner', 'cloud'),
    ('Exoscale', 'exoscale', 'cloud'),
    ('Contabo', 'contabo', 'cloud'),
    ('Linode Akamai', 'akamai', 'cloud'),
    ('BunnyCDN', 'bunny', 'cloud'),
    ('Statically', 'statically', 'cloud'),
    # More DevOps/Git
    ('Gogs', 'gogs', 'devops'),
    ('Gerrit', 'gerrit', 'devops'),
    ('Subversion', 'subversion', 'devops'),
    ('Mercurial', 'mercurial', 'devops'),
    ('GitKraken', 'gitkraken', 'devops'),
    ('Sourcetree', 'sourcetree', 'devops'),
    ('Dependabot', 'dependabot', 'devops'),
    ('OWASP ZAP', 'owaspzap', 'devops'),
    ('Gradle Enterprise', 'gradle', 'devops'),
    ('Gruntjs', 'grunt', 'devops'),
    ('Gulp', 'gulp', 'devops'),
    ('Lerna', 'lerna', 'devops'),
    ('Nx', 'nx', 'devops'),
    ('Turborepo', 'turborepo', 'devops'),
    ('Bazel', 'bazel', 'devops'),
    ('Buck', 'meta', 'devops'),
    # More monitoring
    ('Zabbix', 'zabbix', 'monitoring'),
    ('Nagios', 'nagios', 'monitoring'),
    ('Icinga', 'icinga', 'monitoring'),
    ('Sensu', 'sensu', 'monitoring'),
    ('Cacti', 'cacti', 'monitoring'),
    ('Netbox', 'netbox', 'monitoring'),
    ('LibreNMS', 'librenms', 'monitoring'),
    ('Observium', 'observium', 'monitoring'),
    ('ZabbixCI', 'zabbixci', 'monitoring'),
    ('StatusCake', 'statuscake', 'monitoring'),
    ('Better Stack', 'betterstack', 'monitoring'),
    ('Freshping', 'freshworks', 'monitoring'),
    # More databases
    ('CockroachDB', 'cockroachlabs', 'database'),
    ('ScyllaDB', 'scylladb', 'database'),
    ('ArangoDB', 'arangodb', 'database'),
    ('RethinkDB', 'rethinkdb', 'database'),
    ('Couchbase', 'couchbase', 'database'),
    ('HBase', 'apachehbase', 'database'),
    ('Hive', 'apachehive', 'database'),
    ('Spark', 'apachespark', 'database'),
    ('Hadoop', 'apachehadoop', 'database'),
    ('Flink', 'apacheflink', 'database'),
    ('Druid', 'apachedruid', 'database'),
    ('Pinot', 'apachepinot', 'database'),
    ('Presto', 'prestodb', 'database'),
    ('Trino', 'trino', 'database'),
    ('Dbt', 'dbt', 'database'),
    ('Airbyte', 'airbyte', 'database'),
    ('Stitch', 'stitch', 'database'),
    ('Fivetran', 'fivetran', 'database'),
    ('Meltano', 'meltano', 'database'),
    # More tools
    ('HTTPie', 'httpie', 'tools'),
    ('curl', 'curl', 'tools'),
    ('Helm', 'helm', 'tools'),
    ('k9s', 'k9s', 'tools'),
    ('Lens IDE', 'lens', 'tools'),
    ('DBeaver', 'dbeaver', 'tools'),
    ('TablePlus', 'tableplus', 'tools'),
    ('Beekeeper Studio', 'beekeeperstudio', 'tools'),
    ('DBngin', 'dbngin', 'tools'),
    ('Proxyman', 'proxyman', 'tools'),
    ('Charles Proxy', 'charlesproxy', 'tools'),
    ('Burp Suite', 'portswigger', 'tools'),
    ('OWASP', 'owasp', 'tools'),
    ('Metasploit', 'metasploit', 'tools'),
    ('Kali', 'kalilinux', 'tools'),
    ('Nmap', 'nmap', 'tools'),
    ('Shodan', 'shodan', 'tools'),
    ('VirusTotal', 'virustotal', 'tools'),
    ('Maltego', 'maltego', 'tools'),
    ('Ghidra', 'ghidra', 'tools'),
    ('IDA Pro', 'idasolutions', 'tools'),
    ('VirtualBox', 'virtualbox', 'tools'),
    # More AI/ML
    ('NVIDIA', 'nvidia', 'ai'),
    ('Cohere', 'cohere', 'ai'),
    ('Mistral', 'mistral', 'ai'),
    ('Replicate', 'replicate', 'ai'),
    ('Weights Biases', 'weightsandbiases', 'ai'),
    ('NumPy', 'numpy', 'ai'),
    ('Pandas', 'pandas', 'ai'),
    ('scikit-learn', 'scikitlearn', 'ai'),
    ('OpenCV', 'opencv', 'ai'),
    ('Streamlit', 'streamlit', 'ai'),
    ('Colab', 'googlecolab', 'ai'),
    ('Databricks', 'databricks', 'ai'),
    ('Vertex AI', 'googlecloud', 'ai'),
    ('Sagemaker', 'amazonaws', 'ai'),
    # More homelab
    ('Unraid', 'unraid', 'homelab'),
    ('TrueNAS Scale', 'truenas', 'homelab'),
    ('Proxmox VE', 'proxmox', 'homelab'),
    ('ESXi', 'vmware', 'homelab'),
    ('Cockpit', 'redhat', 'homelab'),
    ('Webmin', 'webmin', 'homelab'),
    ('Coolify', 'coolify', 'homelab'),
    ('Caprover', 'caprover', 'homelab'),
    ('Umbrel', 'umbrel', 'homelab'),
    ('Casaos', 'casaos', 'homelab'),
    ('Yacht', 'yacht', 'homelab'),
    ('Dockge', 'dockge', 'homelab'),
    ('Watchtower', 'watchtower', 'homelab'),
    ('Diun', 'diun', 'homelab'),
    ('Heimdall', 'heimdall', 'homelab'),
    # More security
    ('Authentik', 'authentik', 'security'),
    ('Authelia', 'authelia', 'security'),
    ('Lam', 'ldap', 'security'),
    ('FreeIPA', 'freeipa', 'security'),
    ('OpenLDAP', 'openldap', 'security'),
    ('Passbolt', 'passbolt', 'security'),
    ('Vaultwarden', 'vaultwarden', 'security'),
    ('Crowdsec', 'crowdsec', 'security'),
    ('Fail2ban', 'fail2ban', 'security'),
    ('ModSecurity', 'modsecurity', 'security'),
    ('Endian', 'endian', 'security'),
    ('Zscaler', 'zscaler', 'security'),
    ('Cloudflare Access', 'cloudflare', 'security'),
    # More productivity / ITSM
    ('ServiceNow', 'servicenow', 'productivity'),
    ('Freshdesk', 'freshdesk', 'productivity'),
    ('Freshservice', 'freshworks', 'productivity'),
    ('ManageEngine', 'manageengine', 'productivity'),
    ('Zammad', 'zammad', 'productivity'),
    ('OTRS', 'otrs', 'productivity'),
    ('Redmine', 'redmine', 'productivity'),
    ('YouTrack', 'youtrack', 'productivity'),
    ('Plane', 'plane', 'productivity'),
    ('Clickup', 'clickup', 'productivity'),
    ('Height', 'height', 'productivity'),
    ('Shortcut', 'shortcut', 'productivity'),
    # More frameworks and languages
    ('Bun', 'bun', 'language'),
    ('Deno', 'deno', 'language'),
    ('Zig', 'zig', 'language'),
    ('Nim', 'nim', 'language'),
    ('Crystal', 'crystal', 'language'),
    ('V Lang', 'v', 'language'),
    ('Julia', 'julia', 'language'),
    ('F#', 'fsharp', 'language'),
    ('Clojure', 'clojure', 'language'),
    ('Groovy', 'apachegroovy', 'language'),
    ('Assembly', 'assembly', 'language'),
    ('WebAssembly', 'webassembly', 'language'),
    ('Solidity', 'solidity', 'language'),
    ('COBOL', 'cobol', 'language'),
    # More web/APIs
    ('GraphQL', 'graphql', 'tools'),
    ('gRPC', 'grpc', 'tools'),
    ('Protocol Buffers', 'protodotio', 'tools'),
    ('OpenAPI', 'openapiinitiative', 'tools'),
    ('JSON', 'json', 'tools'),
    ('YAML', 'yaml', 'tools'),
    # Network infrastructure brands
    ('Juniper', 'juniper', 'networking'),
    ('Aruba', 'arubanetworks', 'networking'),
    ('Extreme Networks', 'extremenetworks', 'networking'),
    ('Huawei', 'huawei', 'networking'),
    ('TP-Link', 'tplink', 'networking'),
    ('Netgear', 'netgear', 'networking'),
    ('D-Link', 'dlink', 'networking'),
    ('Sonicwall', 'sonicwall', 'networking'),
    ('Check Point', 'checkpoint', 'networking'),
    ('Barracuda', 'barracuda', 'networking'),
    ('Zscaler2', 'zscaler', 'networking'),
    ('Infoblox', 'infoblox', 'networking'),
    ('BlueCat', 'bluecat', 'networking'),
    ('PowerDNS', 'powerdns', 'networking'),
    ('Bind9', 'isc', 'networking'),
    ('DNSSEC', 'dnssec', 'networking'),
    ('Knot DNS', 'knotdns', 'networking'),
]


def fetch_color_map():
    """Fetch Simple Icons data.json and build title->hex lookup."""
    print("Fetching Simple Icons color data...")
    try:
        req = urllib.request.Request(
            SI_DATA_URL,
            headers={'User-Agent': 'Mozilla/5.0'}
        )
        with urllib.request.urlopen(req, timeout=30) as resp:
            data = json.loads(resp.read().decode('utf-8'))
        color_map = {}
        for icon in data.get('icons', []):
            title = icon.get('title', '').lower()
            slug = icon.get('slug', title.replace(' ', '').lower())
            hex_color = icon.get('hex', '000000')
            color_map[title] = hex_color
            color_map[slug] = hex_color
        print(f"  Loaded {len(color_map)} color entries")
        return color_map
    except Exception as e:
        print(f"  Warning: Could not fetch color data: {e}")
        return {}


def download_svg(slug):
    """Download SVG for slug. Returns SVG content string or None."""
    url = SI_ICON_URL.format(slug=slug)
    try:
        req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0'})
        with urllib.request.urlopen(req, timeout=15) as resp:
            if resp.status == 200:
                return resp.read().decode('utf-8')
    except urllib.error.HTTPError as e:
        if e.code == 404:
            return None
        print(f"  HTTP {e.code} for {slug}")
    except Exception as e:
        print(f"  Error fetching {slug}: {e}")
    return None


def main():
    # Load existing manifest
    with open(MANIFEST_PATH, 'r', encoding='utf-8') as f:
        existing = json.load(f)

    existing_slugs = set(e['slug'] for e in existing)
    print(f"Existing entries: {len(existing)} (unique slugs: {len(existing_slugs)})")

    # Fetch color map
    color_map = fetch_color_map()
    time.sleep(0.2)

    # Deduplicate ADDITIONAL by slug+category (keep first occurrence per slug)
    seen_slugs = set()
    deduped = []
    for name, slug, category in ADDITIONAL:
        key = slug  # allow same slug in different categories if needed
        if key not in seen_slugs:
            seen_slugs.add(key)
            deduped.append((name, slug, category))
        else:
            # Allow same slug in different category (e.g. zscaler in security + networking)
            deduped.append((name, slug, category))

    new_entries = []
    skipped_existing = 0
    skipped_404 = 0
    downloaded = 0

    for name, slug, category in ADDITIONAL:
        # Skip if slug already in manifest
        if slug in existing_slugs:
            skipped_existing += 1
            continue

        # Check if file already exists on disk
        svg_path = os.path.join(ICONS_DIR, f"{slug}.svg")
        if os.path.exists(svg_path):
            # File exists but not in manifest - add to manifest
            color = color_map.get(slug, color_map.get(name.lower(), '000000'))
            entry = {
                "name": name,
                "slug": slug,
                "file": f"{slug}.svg",
                "color": color,
                "category": category
            }
            new_entries.append(entry)
            existing_slugs.add(slug)
            print(f"  [EXISTS] {slug} -> adding to manifest")
            continue

        # Download SVG
        svg_content = download_svg(slug)
        time.sleep(0.05)

        if svg_content is None:
            skipped_404 += 1
            print(f"  [404] {slug}")
            continue

        # Save SVG file
        with open(svg_path, 'w', encoding='utf-8') as f:
            f.write(svg_content)

        # Get color
        color = color_map.get(slug, color_map.get(name.lower(), '000000'))

        entry = {
            "name": name,
            "slug": slug,
            "file": f"{slug}.svg",
            "color": color,
            "category": category
        }
        new_entries.append(entry)
        existing_slugs.add(slug)
        downloaded += 1
        print(f"  [OK] {slug} ({category}) color=#{color}")

    # Combine and sort
    all_entries = existing + new_entries
    all_entries.sort(key=lambda e: (e['category'], e['name'].lower()))

    # Write updated manifest
    with open(MANIFEST_PATH, 'w', encoding='utf-8') as f:
        json.dump(all_entries, f, indent=2, ensure_ascii=False)

    print(f"\n=== SUMMARY ===")
    print(f"Previously in manifest: {len(existing)}")
    print(f"Skipped (already in manifest): {skipped_existing}")
    print(f"Skipped (404): {skipped_404}")
    print(f"Successfully downloaded: {downloaded}")
    print(f"Total entries now in manifest: {len(all_entries)}")

    # Count actual SVG files
    svg_count = len([f for f in os.listdir(ICONS_DIR) if f.endswith('.svg')])
    print(f"Total SVG files on disk: {svg_count}")


if __name__ == '__main__':
    main()
