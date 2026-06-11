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

| EAD Element & Attributes | HTML Output |
|---|---|
| `<emph render="italic">` | `<em>` |
| `<emph render="bold">` | `<strong>` |
| `<emph render="bolditalic">` | `<strong><em>` |
| `<emph render="underline">` | `<u>` |
| `<emph render="boldunderline">` | `<strong><u>` |
| `<emph render="quoted">` | `"` … `"` (curly quotes, no element) |
| `<emph render="super">` | `<sup>` |
| `<emph render="sub">` | `<sub>` |
| `<emph>` (no render attr) | `<em>` |
| `<title render="italic">` | `<cite>` (render attribute ignored; all `<title>` elements map to `<cite>`) |
| `<title>` (no render) | `<cite>` |
| `<lb/>` | `<br>` |
| `<blockquote>` | `<blockquote>` |
| `<list>` / `<item>` | `<ul>` / `<li>` |
| Any unknown/unrecognised element | Text content only (tag stripped, children preserved) |

Text nodes are HTML-escaped. Only the allowlisted tags above appear in the HTML output — no arbitrary XML elements are passed through.

---

## References

- [EAD3 Specification — `<emph>` and `<title>`](https://loc.gov/ead/)
- [Omeka S Documentation](https://omeka.org/s/docs/)
- [NumericDataTypes module](https://github.com/omeka-s-modules/NumericDataTypes) (used as structural reference)