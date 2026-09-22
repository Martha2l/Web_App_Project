const API = '../api/products.php';
const $ = id => document.getElementById(id);
const fields = ['ProductName','SupplierID','CategoryID','QuantityPerUnit','UnitPrice','UnitsInStock','UnitsOnOrder','ReorderLevel','Discontinued'];

async function request(url, options={}) {
  const r = await fetch(url, {headers:{'Content-Type':'application/json'}, ...options});
  const data = await r.json();
  if (!data.success) throw new Error(data.message || 'เกิดข้อผิดพลาด');
  return data.data;
}
function toast(msg, error=false) {
  const t=$('toast'); t.textContent=msg; t.className=error?'show error':'show';
  setTimeout(()=>t.className='',2500);
}
async function load() {
  try {
    const q=$('search').value.trim();
    const data=await request(API+(q?'?q='+encodeURIComponent(q):''));
    $('count').textContent=`${data.length} รายการ`;
    $('rows').innerHTML=data.length ? data.map(p=>`
      <tr>
        <td>#${p.ProductID}</td><td><b>${esc(p.ProductName)}</b><small>${esc(p.QuantityPerUnit||'')}</small></td>
        <td>฿${Number(p.UnitPrice||0).toFixed(2)}</td><td>${p.UnitsInStock}</td><td>${p.UnitsOnOrder}</td>
        <td><span class="status ${p.Discontinued==1?'off':'on'}">${p.Discontinued==1?'เลิกจำหน่าย':'จำหน่าย'}</span></td>
        <td><button onclick="edit(${p.ProductID})" class="small">แก้ไข</button><button onclick="removeProduct(${p.ProductID},'${esc(p.ProductName).replaceAll("'","&#39;")}')" class="small danger">ลบ</button></td>
      </tr>`).join('') : '<tr><td colspan="7" class="empty">ไม่พบข้อมูล</td></tr>';
  } catch(e) { toast(e.message,true); }
}
function esc(s){return String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));}
function openNew(){ $('modalTitle').textContent='เพิ่มสินค้า'; $('productForm').reset(); $('ProductID').value=''; $('modal').showModal(); }
async function edit(id){
  try {
    const p=await request(API+'?id='+id); fields.forEach(f=>$(f).type==='checkbox'?$(f).checked=!!p[f]:$(f).value=p[f]??'');
    $('modalTitle').textContent='แก้ไขสินค้า #'+id; $('modal').showModal();
  } catch(e){toast(e.message,true);}
}
async function removeProduct(id,name){
  if(!confirm(`ต้องการลบ "${name}" หรือไม่?`)) return;
  try{ await request(API+'?id='+id,{method:'DELETE'}); toast('ลบสินค้าเรียบร้อยแล้ว'); load(); }
  catch(e){toast(e.message,true);}
}
$('productForm').addEventListener('submit', async e=>{
  e.preventDefault();
  const data={}; fields.forEach(f=>data[f]=$(f).type==='checkbox'?$(f).checked:$(f).value);
  const id=$('ProductID').value;
  try{
    await request(API+(id?'?id='+id:''),{method:id?'PUT':'POST',body:JSON.stringify(data)});
    $('modal').close(); toast(id?'แก้ไขข้อมูลสำเร็จ':'เพิ่มสินค้าเรียบร้อยแล้ว'); load();
  }catch(e){toast(e.message,true);}
});
$('addBtn').onclick=openNew; $('closeBtn').onclick=()=>$('modal').close(); $('cancelBtn').onclick=()=>$('modal').close();
$('searchBtn').onclick=load; $('resetBtn').onclick=()=>{$('search').value='';load();}; $('search').addEventListener('keydown',e=>{if(e.key==='Enter')load()});
load();
