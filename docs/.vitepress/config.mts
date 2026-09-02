import { createContentLoader, defineConfig, type SiteConfig } from 'vitepress'
import { execSync } from 'node:child_process'
import { writeFileSync } from 'node:fs'
import path from 'node:path'
import { Feed } from 'feed'

function bundleVersion(): string {
  try {
    return execSync('git describe --tags --abbrev=0', { encoding: 'utf8' }).trim()
  } catch {
    return 'Links'
  }
}

const BASE = '/auth-log-bundle/'
const SITE_URL = `https://spiriitlabs.github.io${BASE}`
const OG_IMAGE = `${SITE_URL}og-image.png`
// .xml, not .rss: the client router intercepts links whose extension it doesn't know and 404s on them
const FEED_URL = `${SITE_URL}feed.xml`
const NEWS_GLOB = 'news/*.md'
const PUBLISHER = { '@type': 'Organization', name: 'Spiriit', url: 'https://www.spiriit.com' }

function absoluteUrl(relativePath: string): string {
  return SITE_URL + relativePath.replace(/(?:index)?\.md$/, '')
}

function isNewsArticle(relativePath: string): boolean {
  return relativePath.startsWith('news/') && relativePath !== 'news/index.md'
}

export default defineConfig({
  title: 'Auth Log Bundle',
  description: 'Symfony authentication audit log with geolocation, device detection and security notifications',
  lang: 'en-US',
  base: BASE,
  cleanUrls: true,
  lastUpdated: true,
  sitemap: {
    hostname: SITE_URL,
  },
  head: [
    ['link', { rel: 'icon', href: `${BASE}favicon.svg`, type: 'image/svg+xml' }],
    ['link', { rel: 'apple-touch-icon', href: `${BASE}apple-touch-icon.png`, sizes: '180x180' }],
    ['link', { rel: 'alternate', type: 'application/rss+xml', title: 'Auth Log Bundle news', href: FEED_URL }],
    ['meta', { name: 'google-site-verification', content: 'Mo1IuvBvHPBD6BpU6F2Obspy8c9Hl7r_2JjILEYjvMc' }],
  ],
  transformPageData(pageData, { siteConfig }) {
    const { title: siteTitle, description: siteDescription } = siteConfig.site
    const isHome = pageData.frontmatter.layout === 'home'
    const isArticle = isNewsArticle(pageData.relativePath)
    const title = isHome ? siteTitle : `${pageData.title} | ${siteTitle}`
    const description = pageData.frontmatter.description ?? siteDescription
    const url = absoluteUrl(pageData.relativePath)

    pageData.frontmatter.head ??= []
    pageData.frontmatter.head.push(
      ['link', { rel: 'canonical', href: url }],
      ['meta', { property: 'og:type', content: isArticle ? 'article' : 'website' }],
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

    if (isArticle) {
      const publishedAt = new Date(pageData.frontmatter.date).toISOString()
      const modifiedAt = pageData.lastUpdated ? new Date(pageData.lastUpdated).toISOString() : publishedAt

      pageData.frontmatter.head.push(
        ['meta', { property: 'article:published_time', content: publishedAt }],
        ['meta', { property: 'article:modified_time', content: modifiedAt }],
        ['meta', { property: 'article:author', content: PUBLISHER.name }],
        ['script', { type: 'application/ld+json' }, JSON.stringify({
          '@context': 'https://schema.org',
          '@type': 'BlogPosting',
          headline: pageData.title,
          description,
          datePublished: publishedAt,
          dateModified: modifiedAt,
          image: OG_IMAGE,
          inLanguage: 'en-US',
          mainEntityOfPage: { '@type': 'WebPage', '@id': url },
          author: PUBLISHER,
          publisher: PUBLISHER,
        })],
      )
    }

    if (isHome) {
      pageData.frontmatter.head.push(
        ['script', { type: 'application/ld+json' }, JSON.stringify({
          '@context': 'https://schema.org',
          '@type': 'SoftwareSourceCode',
          name: siteTitle,
          description: siteDescription,
          codeRepository: 'https://github.com/SpiriitLabs/auth-log-bundle',
          programmingLanguage: 'PHP',
          runtimePlatform: 'Symfony',
          license: 'https://opensource.org/licenses/MIT',
          url: SITE_URL,
          image: OG_IMAGE,
          author: PUBLISHER,
          maintainer: PUBLISHER,
        })],
      )
    }
  },
  async buildEnd(siteConfig: SiteConfig) {
    const feed = new Feed({
      title: 'Auth Log Bundle',
      description: 'Release announcements for the Symfony Auth Log Bundle.',
      id: SITE_URL,
      link: SITE_URL,
      language: 'en-US',
      image: OG_IMAGE,
      favicon: `${SITE_URL}favicon.svg`,
      copyright: `Copyright © ${new Date().getFullYear()} Spiriit`,
      feedLinks: { rss: FEED_URL },
    })

    const articles = await createContentLoader(NEWS_GLOB, { render: true }).load()

    articles
      .filter(({ url }) => url !== '/news/')
      .sort((a, b) => +new Date(b.frontmatter.date) - +new Date(a.frontmatter.date))
      .forEach(({ url, frontmatter, html }) => {
        const link = SITE_URL + url.slice(1)

        feed.addItem({
          title: frontmatter.title,
          id: link,
          link,
          description: frontmatter.description,
          // the renderer prefixes internal links with the base only, which most feed readers cannot resolve
          content: html?.replaceAll(`="${BASE}`, `="${SITE_URL}`),
          date: new Date(frontmatter.date),
          author: [PUBLISHER],
        })
      })

    writeFileSync(path.join(siteConfig.outDir, 'feed.xml'), feed.rss2())
  },
  themeConfig: {
    logo: { src: '/logo.svg', alt: 'Auth Log Bundle' },
    nav: [
      { text: 'Guide', link: '/guide/installation' },
      { text: 'Security', link: '/owasp' },
      { text: 'Features', link: '/features/geolocation' },
      { text: 'Advanced', link: '/advanced/custom-notification' },
      { text: 'Upgrade', link: '/upgrade/4.0' },
      { text: 'News', link: '/news/', activeMatch: '/news/' },
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
