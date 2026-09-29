// An authenticated admin may first see WordPress's periodic email reminder.
export async function finishAdminLogin(page, baseURL) {
  const origin = new URL(baseURL).origin;
  const isAdmin = url => url.origin === origin && url.pathname.startsWith('/wp-admin/');
  await page.waitForURL(url => isAdmin(url)
    || (url.origin === origin && url.pathname === '/wp-login.php'
      && url.searchParams.get('action') === 'confirm_admin_email'));
  if (!isAdmin(new URL(page.url()))) {
    if (origin !== 'https://test.hs-manacost.ru') {
      throw new Error('Email reminders may only be postponed on staging');
    }
    // Use core's nonce-protected defer action; never assert that an email is correct.
    await page.locator('a[href*="remind_me_later="]').click();
    await page.waitForURL(isAdmin);
  }
}
