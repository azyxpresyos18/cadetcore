<script>
// Battalion -> Company -> Platoon dropdowns that only offer combinations that exist.
function cascade(bn, co, pl, tree) {
  const fill = (sel, items, label) => { sel.innerHTML = ''; sel.add(new Option(label, '')); items.forEach(v => sel.add(new Option(v, v))); };
  const reset = () => { fill(bn, Object.keys(tree), 'Select...'); fill(co, [], 'Select battalion first...'); fill(pl, [], 'Select company first...'); };
  reset();
  bn.addEventListener('change', () => {
    fill(co, bn.value ? Object.keys(tree[bn.value] || {}) : [], bn.value ? 'Select...' : 'Select battalion first...');
    fill(pl, [], 'Select company first...');
  });
  co.addEventListener('change', () => {
    const list = (bn.value && co.value && tree[bn.value]) ? (tree[bn.value][co.value] || []) : [];
    fill(pl, list, co.value ? 'Select...' : 'Select company first...');
  });
  if (bn.form) bn.form.addEventListener('reset', () => setTimeout(reset, 0));
}
</script>
