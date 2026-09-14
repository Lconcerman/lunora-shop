LUNORA — Star rating style fix
================================

What's in this zip
-------------------
style-stars-fix.css → Replacement CSS for just the .review-form__stars rules.

How to apply it
----------------
1. Open your style.css and find the block that starts with:
       .review-form__stars {
   and ends after the last rule that mentions .review-form__stars
   (there should be about 5-6 rules total: the fieldset, legend,
   input, label, and the :checked/:hover states).

2. Delete that whole old block.

3. Paste in everything from style-stars-fix.css in its place.

4. Save, then commit and push:
       git add style.css
       git commit -m "Improve star rating styling"
       git push origin main

5. Hard-refresh the page in your browser (Ctrl+F5) — CSS is often
   cached, so a normal refresh may not show the change.
