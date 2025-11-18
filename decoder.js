async function loadKeys() {
  const response = await fetch("key.json");
  const config = await response.json();
  if (config.lockdown) throw new Error("System is in lockdown");
  return config.keys;
}

function decodeMessage(encoded, keys) {
  const reverseMap = Object.fromEntries(
    Object.entries(keys).map
