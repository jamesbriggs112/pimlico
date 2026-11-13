/**
 * @file
 * The Drupal and once() globals a component behaviour expects, for tests.
 */
const seen = new WeakMap();

globalThis.Drupal = { behaviors: {}, t: (text) => text };

globalThis.once = (id, selector, context = document) => {
  const elements =
    typeof selector === 'string'
      ? [...context.querySelectorAll(selector)]
      : [selector];
  return elements.filter((element) => {
    const ids = seen.get(element) || new Set();
    if (ids.has(id)) {
      return false;
    }
    ids.add(id);
    seen.set(element, ids);
    return true;
  });
};

/**
 * Loads a component's behaviour file and returns the named behaviour.
 */
export async function loadBehaviour(path, name) {
  await import(path);
  return globalThis.Drupal.behaviors[name];
}
