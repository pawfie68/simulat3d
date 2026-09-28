import fs from 'node:fs';
const p = JSON.parse(fs.readFileSync(new URL('../src/data/privacy.json', import.meta.url)));
const required = ['controller', 'address', 'email', 'mailProvider', 'enquiryRetentionMonths', 'hostingRetention', 'transferSafeguards'];
const missing = required.filter(key => !p[key]);
if (!p.providerReviewConfirmed) missing.push('providerReviewConfirmed');
if (!p.deploymentVerified) missing.push('deploymentVerified');
if (missing.length) {
  console.error('Release blocked. Complete and verify src/data/privacy.json: ' + missing.join(', '));
  process.exit(1);
}
console.log('Privacy configuration complete. Hosting checks must remain current.');
