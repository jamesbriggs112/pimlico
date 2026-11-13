# Info row

Figma "Info row" and "Info row · Mobile": a label and value under a 1px rule. Desktop shows them on one line with the value right-aligned; mobile stacks them (lighter rule). An optional action link ("Find a brand contact →") sits under the row.

`variant: compact` is the Visit banner style: eyebrow labels in a 150px column, white on dark grey.

Rows render as `<dt>`/`<dd>` pairs, so wrap them in a `<dl>` (info_rows does).
