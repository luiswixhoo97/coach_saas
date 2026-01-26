import { CapacitorConfig } from '@capacitor/cli'

const config: CapacitorConfig = {
  appId: 'com.coachsaas.app',
  appName: 'Coach SaaS',
  webDir: 'dist',
  server: {
    androidScheme: 'https',
  },
}

export default config
