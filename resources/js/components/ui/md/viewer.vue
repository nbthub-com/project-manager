<script setup>
import { marked } from "marked";
import { defineProps } from "vue";
import { cn } from "@/lib/utils";
import DOMPurify from "dompurify";

const props = defineProps({
  source: {
    type: String,
    required: false,
  },
  classes: {
    type: String,
    required: false,
  },
});
</script>

<template>
  <div
    :class="cn(
      'markdown bg-primary/10 rounded-lg p-2 text-sm transition cursor-text overflow-auto',
      props.classes
    )"
    v-html="DOMPurify.sanitize(marked.parse(props.source || '-'))"
  ></div>
</template>

<style scoped>
.markdown {
  font-size: 0.95rem;
  line-height: 1.2;
  color: #e2e8f0; /* Tailwind slate-200 */
  padding-left: 13px;
}

/* Headings */
.markdown ::v-deep h1 {
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0.5rem 0 0.75rem;
  padding-bottom: 0.25rem;
  border-bottom: 1px solid #444;
}

.markdown ::v-deep h2 {
  font-size: 1.25rem;
  font-weight: 600;
  margin: 1rem 0 0.5rem;
}

.markdown ::v-deep h3 {
  font-size: 1.1rem;
  font-weight: 600;
  margin: 0.8rem 0 0.4rem;
}

/* Paragraphs */
.markdown ::v-deep p {
  margin: 0.2rem 0;
}

/* Links */
.markdown ::v-deep a {
  color: #60a5fa;
  font-weight: 500;
  text-decoration: underline;
  transition: color 0.2s ease;
}
.markdown ::v-deep a:hover {
  color: #3b82f6;
}

/* Lists */
.markdown ::v-deep ul {
  list-style: none;
  padding-left: 1.2rem;
}
.markdown ::v-deep ul li::before {
  content: "•";
  color: #60a5fa;
  display: inline-block;
  width: 1rem;
  margin-left: -1rem;
}
.markdown ::v-deep ol {
  list-style: decimal;
  padding-left: 1.5rem;
}

/* Blockquote */
.markdown ::v-deep blockquote {
  margin: 1rem 0;
  padding: 0.1rem 1rem;
  border-left: 4px solid #3b82f6;
  background: rgba(59, 130, 246, 0.05);
  border-radius: 0.375rem;
  font-style: italic;
  color: #cbd5e1;
}

/* Code inline */
.markdown ::v-deep code {
  background: rgba(255, 255, 255, 0.1);
  padding: 0.2rem 0.4rem;
  border-radius: 0.375rem;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 0.85em;
  color: #facc15; /* yellow-400 */
}

/* Code blocks */
.markdown ::v-deep pre {
  background: #1e1e2e; /* dark theme like VS Code */
  border-radius: 0.5rem;
  padding: 1rem;
  overflow-x: auto;
  margin: 1rem 0;
  box-shadow: 0 4px 12px rgba(0,0,0,0.25);
}
.markdown ::v-deep pre code {
  color: #f8f8f2;
  font-size: 0.9em;
  line-height: 1.5;
  display: block;
}

/* Tables */
.markdown ::v-deep table {
  width: 100%;
  border-collapse: collapse;
  margin: 1rem 0;
  font-size: 0.9rem;
  border-radius: 0.5rem;
  overflow: hidden;
}

.markdown ::v-deep th {
  background: #334155;
  color: #e2e8f0;
  font-weight: 600;
  padding: 0.75rem;
  text-align: left;
}

.markdown ::v-deep td {
  border-top: 1px solid #475569;
  padding: 0.75rem;
}

.markdown ::v-deep tr:nth-child(even) {
  background: rgba(255, 255, 255, 0.03);
}
.markdown ::v-deep tr:hover {
  background: rgba(59, 130, 246, 0.1);
}
</style>
