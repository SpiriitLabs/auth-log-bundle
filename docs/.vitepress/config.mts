import { defineConfig } from 'vitepress'

export default defineConfig({
  title: 'Auth Log Bundle',
  description: 'Symfony authentication audit log with geolocation, device detection and security notifications',
  lang: 'en-US',
  base: '/auth-log-bundle/',
  cleanUrls: true,
  lastUpdated: true,
  head: [
    ['link', { rel: 'icon', href: '/auth-log-bundle/favicon.svg', type: 'image/svg+xml' }],
  ],
  themeConfig: {
    nav: [
      { text: 'Guide', link: '/guide/installation' },
      { text: 'Features', link: '/features/geolocation' },
      { text: 'Advanced', link: '/advanced/custom-notification' },
      { text: 'Upgrade', link: '/upgrade/3.0' },
      {
        text: 'Links',
        items: [
          { text: 'Packagist', link: 'https://packagist.org/packages/spiriitlabs/auth-log-bundle' },
          { text: 'Changelog', link: 'https://github.com/SpiriitLabs/auth-log-bundle/releases' },
        ],
      },
    ],
    sidebar: [
      {
        text: 'Introduction',
        items: [
          { text: 'What is Auth Log Bundle?', link: '/introduction' },
          { text: 'OWASP best practices', link: '/owasp' },
        ],
      },
      {
        text: 'Getting Started',
        items: [
          { text: 'Installation', link: '/guide/installation' },
          { text: 'Configuration', link: '/guide/configuration' },
          { text: 'User entity', link: '/guide/user-entity' },
          { text: 'Log entity', link: '/guide/log-entity' },
          { text: 'Repository', link: '/guide/repository' },
        ],
      },
      {
        text: 'Features',
        items: [
          { text: 'Geolocation', link: '/features/geolocation' },
          { text: 'Async with Messenger', link: '/features/messenger' },
          { text: 'Events', link: '/features/events' },
          { text: 'Login confirmation', link: '/features/login-confirmation' },
        ],
      },
      {
        text: 'Advanced',
        items: [
          { text: 'Custom notification', link: '/advanced/custom-notification' },
          { text: 'Custom email template', link: '/advanced/email-template' },
          { text: 'Architecture', link: '/advanced/architecture' },
          { text: 'Testing', link: '/advanced/testing' },
        ],
      },
      {
        text: 'Upgrade',
        items: [
          { text: '2.x to 3.0', link: '/upgrade/3.0' },
          { text: '1.x to 2.0', link: '/upgrade/2.0' },
        ],
      },
    ],
    socialLinks: [
      { icon: 'github', link: 'https://github.com/SpiriitLabs/auth-log-bundle' },
    ],
    editLink: {
      pattern: 'https://github.com/SpiriitLabs/auth-log-bundle/edit/main/docs/:path',
      text: 'Edit this page on GitHub',
    },
    search: {
      provider: 'local',
    },
    footer: {
      message: 'Released under the MIT License.',
      copyright: 'Copyright © Spiriit',
    },
  },
})
