/**
 * Logs in to WordPress once as the wp-env administrator and saves the session
 * for the tests that need it.
 */

const { chromium } = require('@playwright/test');
const path = require('path');
const { baseURL } = require('./Environment');

const adminState = path.join(__dirname, '..', '.auth', 'admin.json');

module.exports = async function globalSetup() {
	const browser = await chromium.launch();
	const page = await browser.newPage({ baseURL });

	// wp-env's default administrator.
	await page.goto('/wp-login.php');
	await page.fill('#user_login', 'admin');
	await page.fill('#user_pass', 'password');
	await page.click('#wp-submit');
	await page.waitForURL(/\/wp-admin\//);

	await page.context().storageState({ path: adminState });
	await browser.close();
};

module.exports.adminState = adminState;
