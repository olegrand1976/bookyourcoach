import { createHmac } from 'crypto';

/**
 * Code TOTP (RFC 6238 : SHA-1, 6 chiffres, période de 30 s) calculé à partir d'un
 * secret base32 — ce que ferait l'application d'authentification du gérant.
 */
export function totp(secretBase32: string, timestamp: number = Date.now()): string {
  const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
  let bits = '';
  for (const char of secretBase32.replace(/=+$/, '').toUpperCase()) {
    const index = alphabet.indexOf(char);
    if (index === -1) throw new Error(`Caractère base32 invalide : ${char}`);
    bits += index.toString(2).padStart(5, '0');
  }
  const key = Buffer.from(bits.match(/.{8}/g)!.map((byte) => parseInt(byte, 2)));

  const counter = Buffer.alloc(8);
  counter.writeBigUInt64BE(BigInt(Math.floor(timestamp / 1000 / 30)));

  const hmac = createHmac('sha1', key).update(counter).digest();
  const offset = hmac[hmac.length - 1] & 0x0f;
  const code = (hmac.readUInt32BE(offset) & 0x7fffffff) % 1_000_000;

  return code.toString().padStart(6, '0');
}
