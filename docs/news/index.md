---
title: News
description: "Release announcements for the Auth Log Bundle — what each version changes, and what it asks of your application."
---

<script setup>
import { withBase } from 'vitepress'
import { data as entries } from './news.data.mts'
</script>

# News

What each release changes, and what it asks of your application. <a :href="withBase('/feed.xml')">Subscribe by RSS</a>.

<div class="news-list">
  <article v-for="entry of entries" :key="entry.url" class="news-item">
    <time class="news-item-date" :datetime="entry.date.iso">{{ entry.date.label }}</time>
    <h2 class="news-item-title"><a :href="withBase(entry.url)">{{ entry.title }}</a></h2>
    <p class="news-item-description">{{ entry.description }}</p>
    <a class="news-item-cta" :href="withBase(entry.url)">Read the announcement →</a>
  </article>
</div>
