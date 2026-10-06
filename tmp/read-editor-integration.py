from pathlib import Path
s=Path('apps/admin/src/main.tsx').read_text()
for marker in ['function FormField','function StatusHistory','function cell','<textarea maxLength','function OrderStatusEditor']:
 i=s.find(marker);print(marker+'\n'+s[i:i+900])
s=Path('apps/backend/app/Services/Commerce.php').read_text()
for marker in ['Mail::','function saveSettings']:
 i=s.find(marker);print(marker+'\n'+s[max(0,i-180):i+600])
