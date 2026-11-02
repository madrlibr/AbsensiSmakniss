---
name: SMK Attendance OS
colors:
  surface: '#f8f9ff'
  surface-dim: '#cbdbf5'
  surface-bright: '#f8f9ff'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#eff4ff'
  surface-container: '#e5eeff'
  surface-container-high: '#dce9ff'
  surface-container-highest: '#d3e4fe'
  on-surface: '#0b1c30'
  on-surface-variant: '#434654'
  inverse-surface: '#213145'
  inverse-on-surface: '#eaf1ff'
  outline: '#737686'
  outline-variant: '#c3c5d7'
  surface-tint: '#1353d8'
  primary: '#003fb1'
  on-primary: '#ffffff'
  primary-container: '#1a56db'
  on-primary-container: '#d4dcff'
  inverse-primary: '#b5c4ff'
  secondary: '#495e8a'
  on-secondary: '#ffffff'
  secondary-container: '#b6ccfe'
  on-secondary-container: '#405681'
  tertiary: '#005439'
  on-tertiary: '#ffffff'
  tertiary-container: '#006f4d'
  on-tertiary-container: '#7ff2be'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#dbe1ff'
  primary-fixed-dim: '#b5c4ff'
  on-primary-fixed: '#00174d'
  on-primary-fixed-variant: '#003dab'
  secondary-fixed: '#d8e2ff'
  secondary-fixed-dim: '#b1c6f9'
  on-secondary-fixed: '#001a41'
  on-secondary-fixed-variant: '#304671'
  tertiary-fixed: '#85f8c4'
  tertiary-fixed-dim: '#68dba9'
  on-tertiary-fixed: '#002114'
  on-tertiary-fixed-variant: '#005137'
  background: '#f8f9ff'
  on-background: '#0b1c30'
  surface-variant: '#d3e4fe'
typography:
  display-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 40px
    fontWeight: '800'
    lineHeight: 48px
    letterSpacing: -0.02em
  headline-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 32px
    fontWeight: '700'
    lineHeight: 40px
    letterSpacing: -0.02em
  headline-lg-mobile:
    fontFamily: Plus Jakarta Sans
    fontSize: 26px
    fontWeight: '700'
    lineHeight: 32px
    letterSpacing: -0.01em
  headline-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 24px
    fontWeight: '700'
    lineHeight: 32px
    letterSpacing: -0.01em
  headline-sm:
    fontFamily: Plus Jakarta Sans
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
  title-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 16px
    fontWeight: '600'
    lineHeight: 24px
  body-lg:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  body-md:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
  body-sm:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '400'
    lineHeight: 16px
  label-lg:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '600'
    lineHeight: 20px
    letterSpacing: 0.01em
  label-md:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '600'
    lineHeight: 16px
    letterSpacing: 0.02em
  label-sm:
    fontFamily: Inter
    fontSize: 11px
    fontWeight: '700'
    lineHeight: 14px
    letterSpacing: 0.04em
rounded:
  sm: 0.125rem
  DEFAULT: 0.25rem
  md: 0.375rem
  lg: 0.5rem
  xl: 0.75rem
  full: 9999px
spacing:
  gutter: 1.5rem
  gutter-mobile: 0.75rem
  margin: 2rem
  margin-mobile: 1rem
  space-xs: 0.25rem
  space-sm: 0.5rem
  space-md: 1rem
  space-lg: 1.5rem
  space-xl: 2rem
---

## Brand & Style

This design system targets secondary vocational schools (SMK), serving school administrators, teachers, parents, and students. The interface communicates institutional reliability, precision, and frictionless utility. The design style combines **Corporate Modern** with **High-Contrast Data Functionalism**, optimizing for rapid attendance tracking, strict accountability, and effortless legibility under diverse classroom lighting conditions.

Key emotional traits:
- **Trustworthy & Authoritative:** Instills confidence in administrative data integrity.
- **Vigilant & Clear:** Immediate distinction of student status indicators (Present, Late, Absent, Truant).
- **Industrial Efficiency:** High-speed barcode/QR scanner states, rapid roster navigation, and minimal visual noise.

## Colors

The color palette centers around an institutional dual-blue foundation balanced with explicit, high-contrast semantic states:

- **Primary (`#1A56DB` - Royal Blue):** Interactive actions, primary buttons, active links, focused input rings, scanner reticles.
- **Secondary (`#0F2851` - Deep Navy Blue):** Brand headers, top navigation bars, table header backgrounds, high-hierarchy typography.
- **Tertiary (`#059669` - Emerald Green):** Semantic success state; used strictly for *Hadir* (Present) and *Tepat Waktu* (On Time).
- **Warning / Permitted (`#D97706` - Amber):** Semantic caution state; designated for *Izin/Sakit* (Excused) and *Terlambat* (Tardy).
- **Destructive / Absent (`#DC2626` - Rose Red):** Semantic critical state; designated for *Alpa* (Unexcused Absence) and *Kabur/Meninggalkan Kelas* (Truancy).
- **Neutrals & Surfaces:**
  - Base canvas: `#F8FAFC` (Slate 50)
  - Card/Container background: `#FFFFFF`
  - Subtle secondary container/rail: `#F1F5F9` (Slate 100)
  - Borders and dividers: `#E2E8F0` (Slate 200)
  - Deep text contrast: `#0F172A` (Slate 900)

## Typography

The pairing combines **Plus Jakarta Sans** for structural headlines and quantitative metrics with **Inter** for data tables, lists, and dense operational controls. 

- Use `Plus Jakarta Sans` with tight negative tracking (`-0.01em` to `-0.02em`) on bold weights for summary KPIs (e.g., "98.4% Kehadiran") and major screen titles.
- Use `Inter` for tabular numerals (`tnum`), timestamps, barcode payloads, student IDs (NISN), and status indicators to guarantee unambiguous baseline alignment across rows.

## Layout & Spacing

The layout utilizes a 12-column responsive fluid grid anchored by structured sidebars on desktop and stacked bottom-action bars on tablet and mobile scanner views.

- **Breakpoints:**
  - Mobile: `< 768px` (4-column grid, compact table cards, fixed bottom action bar for handheld barcode scanning).
  - Tablet: `768px - 1024px` (8-column grid, persistent top utility bar, touch-friendly list rows).
  - Desktop: `> 1024px` (12-column grid, persistent 260px administrative navigation sidebar, high-density roster grid).
- **Rhythm:** Layout spaces strictly observe multiples of 4px/8px. Card content adheres to `space-lg` internally, while compact table cells scale down to `space-sm` vertical padding with `space-md` horizontal padding.

## Elevation & Depth

This system avoids heavy drop shadows in favor of **Tonal Layers** combined with **Low-Contrast Outlines** (`border: 1px solid #E2E8F0`) to ensure clean contrast on institutional monitors:

- **Level 0 (Flat Canvas):** `#F8FAFC` base application background.
- **Level 1 (Cards, Modules & Inset Panels):** `#FFFFFF` background with `1px solid #E2E8F0` and subtle ambient tint: `box-shadow: 0 1px 3px 0 rgba(15, 40, 81, 0.05)`.
- **Level 2 (Active Rows, Scan Cards & Flyouts):** `#FFFFFF` with `box-shadow: 0 4px 6px -1px rgba(15, 40, 81, 0.08), 0 2px 4px -2px rgba(15, 40, 81, 0.04)`.
- **Level 3 (Modal Dialogs & Verification Overlays):** `#FFFFFF` bordered by `#CBD5E1` with `box-shadow: 0 20px 25px -5px rgba(15, 40, 81, 0.15), 0 8px 10px -6px rgba(15, 40, 81, 0.1)`.
- **Scanning HUD:** Semi-opaque dark veil (`rgba(15, 40, 81, 0.7)`) with neon-aligned focal reticle in `#1A56DB` or `#059669`.

## Shapes

The design system maintains a **Soft** geometric shape language (`roundedness: 1`) to preserve an organized, professional, and efficient software appearance:

- Standard inputs, buttons, and badges utilize `0.375rem` (6px) to `0.5rem` (8px).
- Main cards and data container modules utilize `0.5rem` (8px).
- Status badges use pill shapes (`9999px`) purely for micro-level indicators to contrast distinctly with rectangular data cells.

## Components

### Buttons
- **Primary:** Background `#1A56DB`, text `#FFFFFF`, font `label-lg`. Hover: `#1E40AF`. Active: `#1E3A8A`. Focus ring: `3px solid rgba(26, 86, 219, 0.3)`.
- **Secondary:** Background `#FFFFFF`, border `1px solid #CBD5E1`, text `#0F2851`. Hover: `#F1F5F9`.
- **Destructive:** Background `#DC2626`, text `#FFFFFF`. Hover: `#B91C1C`.
- **Touch Targets:** Minimum 44px height for mobile scanner confirmation buttons.

### Status Badges (Pills)
Status badges must have high contrast and explicit text labels:
- **Hadir (Present):** Background `#ECFDF5`, text `#065F46`, border `1px solid #A7F3D0`.
- **Terlambat / Izin (Late / Excused):** Background `#FFFBEB`, text `#92400E`, border `1px solid #FDE68A`.
- **Alpa / Kabur (Absent / Truant):** Background `#FEF2F2`, text `#991B1B`, border `1px solid #FECACA`.

### High-Readability Data Tables
- Header row uses `#0F2851` text with a `#F8FAFC` background and distinct bottom border (`2px solid #E2E8F0`).
- Alternating row styling is disabled; instead, use explicit hover states (`#F1F5F9`) and full row select states with a `2px` left border in `#1A56DB`.
- Text columns align left; quantitative counters, NISN, timestamps, and action buttons align right. Tabular numerals (`font-feature-settings: "tnum"`) are mandatory.

### Form Inputs & Barcode Trigger Fields
- Inputs feature `#FFFFFF` fill, `#CBD5E1` border, 8px padding vertical, 12px horizontal.
- Focus state: border color `#1A56DB` with a 3px outer ring `#DBEAFE`.
- **Barcode Input Field:** Features a prominent scan icon indicator, monospace font for student ID entry, and an animated pulsing indicator during active hardware listener mode.

### Rapid-Scan Barcode Interface Component
- **Viewport Card:** Viewport container with `0.5rem` border radius, darkened backdrop, and high-visibility scanning boundary corner marks in `#1A56DB`.
- **Feedback Banner:** Instantaneous full-width status confirmation below the camera feed:
  - Success scan: `#059669` fill with student name, class, and timestamp.
  - Duplicate/Error scan: `#DC2626` fill with an alert tone indicator.