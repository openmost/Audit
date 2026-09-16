<!--
  Matomo - free/libre analytics platform

  Openmost Audit plugin

  @link    https://openmost.com
  @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
-->

<template>
  <ContentBlock feature="true">
    <div class="audit-report">
      <div class="audit-topbar">
        <h2 class="audit-title">{{ translate('Audit_PageTitle') }}</h2>
        <div class="audit-topbar-actions">
          <a class="btn" :href="exportMarkdownUrl">{{ translate('Audit_ExportMarkdown') }}</a>
        </div>
      </div>

      <p class="audit-intro">{{ translate('Audit_PageIntroduction') }}</p>

      <Notification
        v-if="summary.premium > 0"
        css-class="audit-premium-notice"
        context="info"
        :noclear="true"
      >
        <span class="audit-premium-badge">{{ translate('Audit_StatusPremium') }}</span>
        {{ translate('Audit_PremiumIntro', summary.premium) }}
        <a :href="premiumUrl" target="_blank" rel="noopener">{{ translate('Audit_PremiumLinkText') }}</a>
      </Notification>

      <div class="audit-summary">
        <div class="audit-summary-tile">
          <span class="label">{{ translate('Audit_TotalChecks') }}</span>
          <span class="value">{{ summary.total }}</span>
        </div>
        <div class="audit-summary-tile status-pass">
          <span class="label">{{ translate('Audit_StatusPass') }}</span>
          <span class="value">{{ summary.pass }}</span>
        </div>
        <div class="audit-summary-tile status-fail">
          <span class="label">{{ translate('Audit_StatusFail') }}</span>
          <span class="value">{{ summary.fail }}</span>
        </div>
        <div class="audit-summary-tile status-warn">
          <span class="label">{{ translate('Audit_StatusWarn') }}</span>
          <span class="value">{{ summary.warn }}</span>
        </div>
        <div class="audit-summary-tile status-skip">
          <span class="label">{{ translate('Audit_StatusSkip') }}</span>
          <span class="value">{{ summary.skip }}</span>
        </div>
        <div v-if="summary.premium > 0" class="audit-summary-tile status-premium">
          <span class="label">{{ translate('Audit_StatusPremium') }}</span>
          <span class="value">{{ summary.premium }}</span>
        </div>
      </div>

      <div class="audit-toolbar">
        <div class="audit-filter-field">
          <label class="audit-filter-label" for="audit-filter-category">
            {{ translate('Audit_FilterCategory') }}
          </label>
          <select id="audit-filter-category" v-model="filters.category">
            <option value="">{{ translate('Audit_FilterAll') }}</option>
            <option v-for="cat in categories" :key="cat" :value="cat">
              {{ labelForCategory(cat) }}
            </option>
          </select>
        </div>

        <div class="audit-filter-field">
          <label class="audit-filter-label" for="audit-filter-severity">
            {{ translate('Audit_FilterSeverity') }}
          </label>
          <select id="audit-filter-severity" v-model="filters.severity">
            <option value="">{{ translate('Audit_FilterAll') }}</option>
            <option value="critical">{{ translate('Audit_SeverityCritical') }}</option>
            <option value="high">{{ translate('Audit_SeverityHigh') }}</option>
            <option value="medium">{{ translate('Audit_SeverityMedium') }}</option>
            <option value="low">{{ translate('Audit_SeverityLow') }}</option>
            <option value="info">{{ translate('Audit_SeverityInfo') }}</option>
          </select>
        </div>

        <div class="audit-filter-field">
          <label class="audit-filter-label" for="audit-filter-status">
            {{ translate('Audit_FilterStatus') }}
          </label>
          <select id="audit-filter-status" v-model="filters.status">
            <option value="">{{ translate('Audit_FilterAll') }}</option>
            <option value="pass">{{ translate('Audit_StatusPass') }}</option>
            <option value="fail">{{ translate('Audit_StatusFail') }}</option>
            <option value="warn">{{ translate('Audit_StatusWarn') }}</option>
            <option value="skip">{{ translate('Audit_StatusSkip') }}</option>
            <option value="premium">{{ translate('Audit_StatusPremium') }}</option>
          </select>
        </div>

        <div class="audit-filter-field audit-filter-field--search">
          <label class="audit-filter-label" for="audit-filter-search">
            {{ translate('Audit_FilterSearch') }}
          </label>
          <input
            id="audit-filter-search"
            type="search"
            v-model="filters.query"
            :placeholder="translate('Audit_FilterSearch')"
          />
        </div>
      </div>

      <div v-if="filteredFindings.length === 0">
        <p><em>{{ translate('Audit_NoFindings') }}</em></p>
      </div>

      <div
        v-for="category in orderedCategoryKeys"
        :key="category"
        class="audit-category"
      >
        <div class="audit-category-head">
          <h3>{{ labelForCategory(category) }}</h3>
          <div class="audit-category-counts">
            <span
              v-for="(count, status) in categoryStatusCounts[category]"
              :key="status"
              v-show="count > 0"
              :class="['category-count', `status-${status}`]"
            >
              {{ count }} {{ translate(`Audit_Status${status.charAt(0).toUpperCase() + status.slice(1)}`) }}
            </span>
          </div>
        </div>
        <Finding
          v-for="finding in groupedFindings[category]"
          :key="finding.itemId"
          :finding="finding"
          :premium-url="premiumUrl"
        />
      </div>
    </div>
  </ContentBlock>
</template>

<script lang="ts">
import { defineComponent, PropType } from 'vue';
import { ContentBlock, Notification, translate } from 'CoreHome';
import Finding from './Finding.vue';

export interface FindingDto {
  itemId: string;
  title: string;
  category: string;
  status: string;
  severity: string;
  detail: string;
  currentValue: string | null;
  expectedValue: string | null;
  recommendation: string | null;
  codeSnippet: string | null;
  references: string[];
}

export interface CategoryMeta {
  id: string;
  label: string;
}

export interface ReportDto {
  generatedAt: string;
  matomoVersion: string;
  phpVersion: string;
  pluginVersion: string;
  summary: Record<string, number>;
  findings: FindingDto[];
  metadata?: {
    categories?: CategoryMeta[];
  };
}

const severityWeight: Record<string, number> = {
  critical: 0, high: 1, medium: 2, low: 3, info: 4,
};

export default defineComponent({
  components: { ContentBlock, Finding, Notification },
  props: {
    report: {
      type: Object as PropType<ReportDto>,
      required: true,
    },
    exportMarkdownUrl: { type: String, required: true },
    pluginVersion: { type: String, required: true },
    premiumUrl: { type: String, required: true },
  },
  data() {
    return {
      filters: {
        category: '', severity: '', status: '', query: '',
      },
    };
  },
  computed: {
    summary(): Record<string, number> {
      return this.report.summary || {};
    },
    categoryLabelMap(): Record<string, string> {
      const map: Record<string, string> = {};
      (this.report.metadata?.categories || []).forEach((c) => {
        if (c && c.id) map[c.id] = c.label || c.id;
      });
      return map;
    },
    categories(): string[] {
      const set = new Set<string>();
      (this.report.findings || []).forEach((f: FindingDto) => set.add(f.category));
      return Array.from(set).sort();
    },
    filteredFindings(): FindingDto[] {
      const q = (this.filters.query || '').toLowerCase().trim();
      return (this.report.findings || []).filter((f: FindingDto) => {
        if (this.filters.category && f.category !== this.filters.category) return false;
        if (this.filters.severity && f.severity !== this.filters.severity) return false;
        if (this.filters.status && f.status !== this.filters.status) return false;
        if (q && !(`${f.title} ${f.itemId} ${f.detail}`.toLowerCase().includes(q))) return false;
        return true;
      });
    },
    groupedFindings(): Record<string, FindingDto[]> {
      const groups: Record<string, FindingDto[]> = {};
      this.filteredFindings.forEach((f: FindingDto) => {
        if (!groups[f.category]) groups[f.category] = [];
        groups[f.category].push(f);
      });
      Object.keys(groups).forEach((cat) => {
        // Checks that ran come first, the premium ones they unlock after.
        groups[cat].sort(
          (a, b) => Number(a.status === 'premium') - Number(b.status === 'premium')
            || (severityWeight[a.severity] ?? 9) - (severityWeight[b.severity] ?? 9),
        );
      });
      return groups;
    },
    orderedCategoryKeys(): string[] {
      return Object.keys(this.groupedFindings).sort();
    },
    categoryStatusCounts(): Record<string, Record<string, number>> {
      const result: Record<string, Record<string, number>> = {};
      Object.keys(this.groupedFindings).forEach((cat) => {
        const counts = {
          fail: 0, warn: 0, pass: 0, skip: 0, premium: 0,
        };
        this.groupedFindings[cat].forEach((f) => {
          if (counts[f.status as keyof typeof counts] !== undefined) {
            counts[f.status as keyof typeof counts] += 1;
          }
        });
        result[cat] = counts;
      });
      return result;
    },
  },
  methods: {
    translate,
    /**
     * Prefer the localised label (`Audit_Category<Pascal>`) so the
     * category heading shows "Base de données" in FR rather than the
     * English YAML label. Falls back to the YAML-provided label, then
     * to a prettified version of the raw id so we never render a
     * lower-case kebab slug as a heading.
     */
    labelForCategory(id: string): string {
      const key = `Audit_Category${id
        .split('-')
        .map((p) => p.charAt(0).toUpperCase() + p.slice(1))
        .join('')}`;
      const translated = translate(key);
      if (translated && translated !== key) {
        return translated;
      }
      if (this.categoryLabelMap[id]) {
        return this.categoryLabelMap[id];
      }
      return id
        .split('-')
        .map((p) => p.charAt(0).toUpperCase() + p.slice(1))
        .join(' ');
    },
  },
});
</script>
