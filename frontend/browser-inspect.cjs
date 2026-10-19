const { chromium } = require('playwright');
(async()=>{const b=await chromium.connectOverCDP('http://127.0.0.1:9222');const p=b.contexts()[0].pages().at(-1);console.log('URL',p.url());console.log(await p.locator('body').innerText());console.log('options',await p.locator('#queue-request option').allTextContents());})().catch(e=>{console.error(e);process.exit(1)});
