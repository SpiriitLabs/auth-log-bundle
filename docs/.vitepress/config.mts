import { defineConfig } from 'vitepress'
import { execSync } from 'node:child_process'

function bundleVersion(): string {
  try {
    return execSync('git describe --tags --abbrev=0', { encoding: 'utf8' }).trim()
  } catch {
    return 'Links'
  }
}

const SITE_URL = 'https://spiriitlabs.github.io/auth-log-bundle/'
const OG_IMAGE = `${SITE_URL}og-image.png`

export default defineConfig({
  title: 'Auth Log Bundle',
  description: 'Symfony authentication audit log with geolocation, device detection and security notifications',
  lang: 'en-US',
  base: '/auth-log-bundle/',
  cleanUrls: true,
  lastUpdated: true,
  sitemap: {
    hostname: SITE_URL,
  },
  head: [
    ['link', { rel: 'icon', href: '/auth-log-bundle/favicon.svg', type: 'image/svg+xml' }],
    ['link', { rel: 'apple-touch-icon', href: '/auth-log-bundle/apple-touch-icon.png', sizes: '180x180' }],
  ],
  transformPageData(pageData, { siteConfig }) {
    const { title: siteTitle, description: siteDescription } = siteConfig.site
    const isHome = pageData.frontmatter.layout === 'home'
    const title = isHome ? siteTitle : `${pageData.title} | ${siteTitle}`
    const description = pageData.frontmatter.description ?? siteDescription
    const url = SITE_URL + pageData.relativePath.replace(/(?:index)?\.md$/, '')

    pageData.frontmatter.head ??= []
    pageData.frontmatter.head.push(
      ['meta', { property: 'og:type', content: 'website' }],
      ['meta', { property: 'og:site_name', content: siteTitle }],
      ['meta', { property: 'og:title', content: title }],
      ['meta', { property: 'og:description', content: description }],
      ['meta', { property: 'og:url', content: url }],
      ['meta', { property: 'og:image', content: OG_IMAGE }],
      ['meta', { property: 'og:image:width', content: '1200' }],
      ['meta', { property: 'og:image:height', content: '630' }],
      ['meta', { property: 'og:image:alt', content: 'authlog, a Symfony bundle by SpiriitLabs' }],
      ['meta', { name: 'twitter:card', content: 'summary_large_image' }],
      ['meta', { name: 'twitter:title', content: title }],
      ['meta', { name: 'twitter:description', content: description }],
      ['meta', { name: 'twitter:image', content: OG_IMAGE }],
    )
  },
  themeConfig: {
    logo: { src: '/logo.svg', alt: 'Auth Log Bundle' },
    nav: [
      { text: 'Guide', link: '/guide/installation' },
      { text: 'Security', link: '/owasp' },
      { text: 'Features', link: '/features/geolocation' },
      { text: 'Advanced', link: '/advanced/custom-notification' },
      { text: 'Upgrade', link: '/upgrade/4.0' },
      {
        text: bundleVersion(),
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
          { text: 'Security & OWASP', link: '/owasp' },
        ],
      },
      {
        text: 'Getting Started',
        items: [
          { text: 'Installation', link: '/guide/installation' },
          { text: 'Configuration', link: '/guide/configuration' },
          { text: 'Supported login types', link: '/guide/supported-authentication' },
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
          { text: 'Disavowal reactions', link: '/features/disavowal-reactions' },
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
          { text: '3.x to 4.0', link: '/upgrade/4.0' },
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
      message: 'Built and maintained by <a href="https://www.spiriit.com" target="_blank" rel="noreferrer">Spiriit</a> — released under the MIT License.',
      copyright: 'Copyright © Spiriit',
    },
  },
})
