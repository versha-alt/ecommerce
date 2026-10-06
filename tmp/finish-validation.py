from pathlib import Path
p=Path('apps/admin/src/main.tsx');s=p.read_text();s=s.replace(" step={field.type==='number'?'any':undefined}",'');s=s.replace('role="alert" role="alert"','role="alert"');p.write_text(s)
p=Path('apps/admin/src/validation.tsx');s=p.read_text().replace("if(field.type==='password'){rules.minLength=12;rules.maxLength=1024;}","if(key==='password'){rules.minLength=12;rules.maxLength=1024;}");p.write_text(s)
p=Path('apps/admin/src/styles.css');s=p.read_text().replace('`n.field-error','\n.field-error');p.write_text(s)
p=Path('apps/backend/app/Services/Commerce.php');s=p.read_text();s=s.replace("'image' => 'nullable|string|max:2000', 'banner' => 'nullable|string|max:2000'", "'image' => 'nullable|string|max:2000', 'banner' => 'nullable|string|max:2000', 'seo_title' => 'nullable|string|max:250', 'seo_description' => 'nullable|string|max:2000', 'specifications' => 'nullable|string|max:20000', 'warranty' => 'nullable|string|max:2000'")
s=s.replace("'categories' => ['slug' => 'required|regex:","'categories' => ['slug' => 'required|string|max:180|regex:")
s=s.replace("'attributes' => ['code' => 'required|regex:","'attributes' => ['filterable' => 'nullable|boolean', 'required' => 'nullable|boolean', 'unit' => 'nullable|string|max:80', 'options.*' => 'string|max:180', 'code' => 'required|string|max:80|regex:")
s=s.replace("'customers' => ['email'", "'customers' => ['marketing_consent' => 'nullable|boolean', 'company' => 'nullable|string|max:180', 'county' => 'nullable|string|max:180', 'email'")
s=s.replace("'pages' => ['slug' => 'required|regex:","'banners' => ['placement' => 'required|in:Hero slider,Promotional banner,Featured brand,Featured product', 'headline' => 'nullable|string|max:250', 'link' => ['nullable', 'string', 'max:2000', 'regex:~^(https?://|/(?!/)|\\#)~']],\n            'pages' => ['slug' => 'required|string|max:180|regex:")
s=s.replace("'event' => 'required|string|max:100'", "'event' => 'required|in:Order confirmation,Order status update,Admin new order,Payment successful,Password reset'")
s=s.replace("'status_history' => [['from' => null, 'to' => 'Pending', 'at' => now()->toISOString(), 'actor' => $customer['id'], 'note' => 'Order placed']], ", '')
s=s.replace("$this->fail('This SKU already exists.');", "throw ValidationException::withMessages(['sku' => 'This SKU already exists.']);")
s=s.replace("$this->fail('Sale price must be lower than or equal to regular price.');", "throw ValidationException::withMessages(['sale_price' => 'Sale price must be lower than or equal to regular price.']);")
s=s.replace("$this->fail('This email is already in use.');", "throw ValidationException::withMessages(['email' => 'This email is already in use.']);")
s=s.replace("'password' => ($id ? 'nullable' : 'required').'|string|min:12'", "'password' => ($id ? 'nullable' : 'required').'|string|min:12|max:1024'")
needle="Validator::make($input, ['store_name' =>"
a=s.index(needle,s.index('public function saveSettings'));b=s.index('\n\n',a)
s=s[:a]+'''Validator::make($input, [
            'store_name' => 'required|string|max:180', 'version' => 'required|integer|min:1',
            'email' => 'nullable|email|max:180', 'mail_from' => 'nullable|email|max:180',
            'phone' => 'nullable|string|max:50', 'whatsapp' => 'nullable|string|max:50', 'address' => 'nullable|string|max:2000',
            'logo' => 'nullable|string|max:2000', 'facebook' => 'nullable|url:http,https|max:2000', 'instagram' => 'nullable|url:http,https|max:2000', 'cdn_url' => 'nullable|url:http,https|max:2000',
            'mail_transport' => 'nullable|in:Log (local preview),SMTP,Amazon SES',
            'smtp_host' => 'required_if:mail_transport,SMTP|nullable|string|max:250',
            'smtp_port' => 'required_if:mail_transport,SMTP|nullable|integer|min:1|max:65535',
            'smtp_username' => 'nullable|string|max:250', 'smtp_password' => 'nullable|string|max:2000',
            'ga4_id' => ['nullable', 'regex:/^G-[A-Z0-9]+$/'], 'meta_pixel_id' => ['nullable', 'regex:/^[0-9]+$/'],
        ])->validate();'''+s[b:]
p.write_text(s)
