async function loadKeys() {
  const response = await fetch("key.json");
  const config = await response.json();
  if (config.lockdown) throw new Error("System is in lockdown");
  return config.keys;
}

function encodeMessage(message, keys) {
  return message.split("").map(char => {
    const key = char === " " ? "space" : char.toLowerCase();
    return keys[key] || `[?${char}]`;
  }).join(" ");
}
