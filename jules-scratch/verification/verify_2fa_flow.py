from playwright.sync_api import sync_playwright, expect

def run(playwright):
    browser = playwright.chromium.launch()
    page = browser.new_page()

    try:
        # Go to the login page
        page.goto("http://localhost:8000/admin/login")

        # Fill in the credentials
        page.get_by_label("Username").fill("admin")
        page.get_by_label("Password").fill("password")

        # Click the login button
        page.get_by_role("button", name="Login").click()

        # Expect to be redirected to the 2FA page
        expect(page).to_have_url("http://localhost:8000/admin/2fa")
        expect(page.get_by_role("heading", name="Two-Factor Authentication")).to_be_visible()

        # Take a screenshot
        page.screenshot(path="jules-scratch/verification/2fa_page.png")
        print("Screenshot taken successfully.")

    except Exception as e:
        print(f"An error occurred: {e}")
        page.screenshot(path="jules-scratch/verification/error.png")

    finally:
        browser.close()

with sync_playwright() as playwright:
    run(playwright)