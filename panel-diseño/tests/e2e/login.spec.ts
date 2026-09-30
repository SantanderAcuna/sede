import { test, expect } from '@playwright/test';

test.describe('Flujo de login', () => {
  test('redirige a MFA tras enviar credenciales válidas', async ({ page }) => {
    await page.goto('/login');
    await page.getByLabel('Usuario o cédula').fill('admin@santamarta.gov.co');
    await page.getByLabel('Contraseña').fill('SuperSecreto123');
    await page.getByRole('button', { name: 'Ingresar' }).click();
    await expect(page).toHaveURL(/\/mfa/);
  });

  test('muestra error con código MFA inválido', async ({ page }) => {
    await page.goto('/mfa');
    await page.getByLabel('Código MFA').fill('abc');
    await page.getByRole('button', { name: 'Verificar e ingresar' }).click();
    await expect(page.getByText(/6 dígitos/i)).toBeVisible();
  });
});
