import assert from 'node:assert/strict';
import {test} from 'node:test';
import {readFileSync} from 'node:fs';
import {webcrypto} from 'node:crypto';
import {transformSync} from 'esbuild';
import vm from 'node:vm';
const source=readFileSync(new URL('./request-id.ts',import.meta.url),'utf8');
const code=transformSync(source,{loader:'ts',format:'cjs'}).code;
function load(crypto){const context={crypto,Uint8Array,exports:{},module:{exports:{}}};vm.runInNewContext(code,context);return context.module.exports.requestId;}
test('uses native UUID when available',()=>{assert.equal(load({randomUUID:()=> 'native-id'})(),'native-id');});
test('generates unique valid v4 UUIDs without randomUUID on HTTP',()=>{const create=load({getRandomValues:array=>webcrypto.getRandomValues(array)});const ids=Array.from({length:1000},()=>create());assert.equal(new Set(ids).size,1000);for(const id of ids)assert.match(id,/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/);});
