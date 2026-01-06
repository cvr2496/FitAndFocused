import { exec } from 'child_process';
import { promisify } from 'util';

const execAsync = promisify(exec);

async function globalSetup() {
  console.log('Setting up test environment...');
  
  try {
    // Seed demo data before running tests
    console.log('Seeding demo user data...');
    await execAsync('php artisan demo:seed --fresh');
    console.log('Demo data seeded successfully');
  } catch (error) {
    console.error('Failed to seed demo data:', error);
    throw error;
  }
}

export default globalSetup;

