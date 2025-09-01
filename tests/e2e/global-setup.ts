import { FullConfig } from '@playwright/test';
import fs from 'fs';
import path from 'path';

// Ensure required directories exist before tests run
export default async function globalSetup(config: FullConfig) {
  const authDir = path.resolve(__dirname, '../.auth');
  if (!fs.existsSync(authDir)) {
    fs.mkdirSync(authDir, { recursive: true });
  }
}

