# EAD XML Data Type

An [Omeka S](https://omeka.org/s/) module that registers a custom **`ead:xml`** data type, allowing archivists to enter metadata values containing EAD XML inline markup (such as `<emph render="italic">The Great Gatsby</emph>`) and have them rendered as styled HTML (e.g., `<em>The Great Gatsby</em>`) in public and admin views.

In standard Omeka S, property values are escaped as plain text before display — there is no built-in way to embed styling in metadata values. This module solves that by treating the raw stored value as an EAD XML fragment and transforming it to safe, allowlisted HTML at render time.

---

## Requirements

- **Omeka S** `^4.0.0`
- PHP `^8.0` with the `dom` extension (bundled with PHP by default)

---

## Installation

1. Download or clone this repository into your Omeka S `modules/` directory and name the folder **`EadXmlDataType`**:

   ```
   <omeka-s>/modules/EadXmlDataType/
   ```

2. In the Omeka S admin panel, navigate to **Modules** and click **Install** next to *EAD XML Data Type*.

No database migrations or additional configuration are required.

---

## Usage

Once installed, a new data type called **EAD XML** will appear in the data-type dropdown when editing resource property values. Paste or type an EAD XML fragment into the textarea; the value is stored as-is and rendered as HTML when displayed.

### Example

| Raw input (stored value) | Rendered output |
|---|---|
| `<emph render="italic">The Great Gatsby</emph> by <emph render="bold">F. Scott Fitzgerald</emph>` | *The Great Gatsby* by **F. Scott Fitzgerald** |
| `<title>Walden</title>` | <cite>Walden</cite> |
| `Line one<lb/>Line two` | Line one<br>Line two |

---

## EAD → HTML Conversion Table

The conversion is based on the [EAD3 Cookbook XSLT stylesheet](https://github.com/saa-ead-roundtable/ead3-stylesheets/blob/master/original-ead3-cookbook/EAD3toHTML.xsl).

### `<emph>` — inline emphasis

| EAD | HTML Output |
|---|---|
| `<emph render="italic">` | `<em>…</em>` |
| `<emph render="bold">` | `<strong>…</strong>` |
| `<emph render="bolditalic">` | `<strong><em>…</em></strong>` |
| `<emph render="underline">` | `<u>…</u>` |
| `<emph render="boldunderline">` | `<strong><u>…</u></strong>` |
| `<emph render="quoted">` or `render="doublequote"` | `"…"` (curly double quotes, no element) |
| `<emph render="singlequote">` | `'…'` (curly single quotes, no element) |
| `<emph render="bolddoublequote">` | `<strong>"…"</strong>` |
| `<emph render="boldsinglequote">` | `<strong>'…'</strong>` |
| `<emph render="super">` | `<sup>…</sup>` |
| `<emph render="sub">` | `<sub>…</sub>` |
| `<emph render="smcaps">` | `<span style="font-variant:small-caps">…</span>` |
| `<emph render="boldsmcaps">` | `<strong><span style="font-variant:small-caps">…</span></strong>` |
| `<emph>` (no render / unrecognised render) | `<em>…</em>` |

### `<title>` — referenced work title

All `<title>` elements are wrapped in `<cite>` (HTML5 semantic element for cited works). The `render` attribute adds inner styling.

| EAD | HTML Output |
|---|---|
| `<title>` or `<title render="italic">` | `<cite>…</cite>` |
| `<title render="bold">` | `<cite><strong>…</strong></cite>` |
| `<title render="bolditalic">` | `<cite><strong><em>…</em></strong></cite>` |
| `<title render="underline">` | `<cite><u>…</u></cite>` |
| `<title render="boldunderline">` | `<cite><strong><u>…</u></strong></cite>` |
| `<title render="quoted">` or `render="doublequote"` | `<cite>"…"</cite>` |
| `<title render="singlequote">` | `<cite>'…'</cite>` |
| `<title render="bolddoublequote">` | `<cite><strong>"…"</strong></cite>` |
| `<title render="boldsinglequote">` | `<cite><strong>'…'</strong></cite>` |
| `<title render="super">` | `<cite><sup>…</sup></cite>` |
| `<title render="sub">` | `<cite><sub>…</sub></cite>` |
| `<title render="smcaps">` | `<cite><span style="font-variant:small-caps">…</span></cite>` |
| `<title render="boldsmcaps">` | `<cite><strong><span style="font-variant:small-caps">…</span></strong></cite>` |

### Structural / block elements

| EAD | HTML Output |
|---|---|
| `<lb/>` | `<br>` |
| `<p>` | `<p>…</p>` |
| `<blockquote>` | `<blockquote>…</blockquote>` |
| `<list>` / `<item>` | `<ul>…</ul>` / `<li>…</li>` |
| `<head>` | `<strong>…</strong>` |
| `<ref href="…">` | `<a href="…">…</a>` |
| `<ref target="…">` | `<a href="#…">…</a>` |
| `<table>` / `<tgroup>` / `<thead>` / `<tbody>` / `<row>` / `<entry>` | `<table>` / (passthrough) / `<thead>` / `<tbody>` / `<tr>` / `<td>` |
| Any unrecognised element | Text content only (tag stripped, children preserved) |

Text nodes are HTML-escaped throughout. Only the allowlisted tags above appear in the HTML output.

---

## Using the API

### Create an item with an `ead:xml` property value

To set a property value with the `ead:xml` data type via the Omeka S REST API, include the data type name in the JSON-LD payload:

```json
POST /api/items
Content-Type: application/json

{
  "dcterms:title": [
    {
      "type": "ead:xml",
      "@value": "<emph render=\"italic\">The Great Gatsby</emph>"
    }
  ],
  "o:resource_class": null
}
```

The `type` key must be `"ead:xml"`. Omeka S routes the value to the `EadXml` data type for hydration and rendering.

**Updating an existing item** — issue a `PUT /api/items/{id}` request with the same JSON-LD structure.

**Reading back a value** — `GET /api/items/{id}` returns:

```json
"dcterms:title": [
  {
    "type": "ead:xml",
    "@value": "<emph render=\"italic\">The Great Gatsby</emph>",
    "display_title": "The Great Gatsby"
  }
]
```

---

## Using a Resource Template

Resource templates let you pin a property to the `EAD XML` data type so editors always see the right input widget without manually selecting the type each time.

1. In the Omeka S admin panel, go to **Resource Templates** → **Add new resource template**.
2. Give the template a name (e.g., *Archival Item with EAD markup*).
3. Click **Add property** and choose the property you want to use EAD markup for (e.g., `dcterms:description`).
4. In the **Data type** column for that property, open the dropdown and select **EAD → EAD XML**.
5. Optionally set **Default language** and mark the property as required.
6. Save the template.

When editors create or edit items using this template, the chosen property will automatically display the EAD XML textarea instead of the plain-text input. The `ead:xml` type is stored on the value and used for rendering.

---

## Batch Converting Existing Values

The module implements Omeka S's `ConversionTargetInterface`, so `ead:xml` appears as a target in the built-in **Convert data type** batch-edit action.

1. Go to **Items** (or **Media**) in the admin panel.
2. Select the items whose values you want to convert, or select all.
3. Click **Batch actions** → **Edit selected** (or **Edit all**).
4. In the **Batch edit** form, find the **Convert data type** section.
5. Choose the property and select **EAD XML** as the target data type.
6. Submit the form.

Omeka S will convert all matching values in the selected resources to `ead:xml`. The raw text content is preserved — no data is lost. The conversion succeeds for any value that has a non-empty text string; values with only a URI or linked resource are skipped.

---

## References

- [EAD3 Specification — `<emph>` and `<title>`](https://loc.gov/ead/)
- [EAD3 Cookbook XSLT stylesheet](https://github.com/saa-ead-roundtable/ead3-stylesheets/blob/master/original-ead3-cookbook/EAD3toHTML.xsl) (conversion reference)
- [Omeka S Documentation](https://omeka.org/s/docs/)
- [NumericDataTypes module](https://github.com/omeka-s-modules/NumericDataTypes) (used as structural reference)