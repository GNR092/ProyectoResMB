let migracionesChoices = {};
let migracionPreviewHash = null;

function initMigracionesCatalogo(){
    const btnMigrar = document.getElementById('btn-migrar-catalogo');
    const pantallaLista = document.getElementById('pantalla-lista-catalogo');
    const pantallaForm = document.getElementById('pantalla-form-catalogo');
    const pantallaMigrar = document.getElementById('pantalla-migrar-catalogo');
    if(!btnMigrar || !pantallaMigrar) return;

    const btnRegresar = document.getElementById('btn-regresar-migrar');
    const btnADestino = document.getElementById('btn-migrar-a-destino');
    const btnVolverOrigen = document.getElementById('btn-migrar-volver-origen');
    const btnPreview = document.getElementById('btn-migrar-preview');
    const btnEjecutar = document.getElementById('btn-migrar-ejecutar');
    const badge = document.getElementById('migrar-step-badge');
    const step1 = document.getElementById('migrar-step1');
    const step2 = document.getElementById('migrar-step2');
    const previewBox = document.getElementById('migrar-preview');

    // Destroy previous Choices
    Object.values(migracionesChoices).forEach(c=>{ try{c.destroy();}catch(e){} });
    migracionesChoices = {};
    migracionPreviewHash = null;
    if(previewBox) previewBox.classList.add('hidden');
    const resEl = document.getElementById('migrar-result'); if(resEl) resEl.innerHTML='';
    const prevContent = document.getElementById('migrar-preview-content'); if(prevContent) prevContent.innerHTML='';

    // Init Choices for RS/Seg/Place/UO/Grupo orig/dest
    const ids = ['mig-orig-rs','mig-orig-seg','mig-orig-place','mig-orig-uo','mig-orig-grupo','mig-dest-rs','mig-dest-seg','mig-dest-place','mig-dest-uo','mig-dest-grupo'];
    ids.forEach(id=>{
        const el = document.getElementById(id);
        if(el && typeof Choices !== 'undefined'){
            try{ migracionesChoices[id] = new Choices(el,{removeItemButton:false,itemSelectText:'',searchPlaceholderValue:'Buscar...',shouldSort:false}); }catch(e){}
        }
    });

    // Capture original options for filtering
    const origOptions = captureMigOptions();

    function captureMigOptions(){
        const map = {};
        ['mig-orig-seg','mig-orig-place','mig-orig-uo','mig-orig-grupo','mig-dest-seg','mig-dest-place'].forEach(id=>{
            const el = document.getElementById(id);
            if(el) map[id] = Array.from(el.options).map(o=>({value:o.value, label:o.text, rs:o.dataset.rs, seg:o.dataset.seg, place:o.dataset.place, unidad:o.dataset.unidad}));
        });
        // For dest UO/Grupo we rebuild from global lists
        map['mig-dest-uo-orig'] = Array.from(document.getElementById('mig-orig-uo')?.options || []).map(o=>({value:o.value,label:o.text,place:o.dataset.place}));
        map['mig-dest-grupo-orig'] = Array.from(document.getElementById('mig-orig-grupo')?.options || []).map(o=>({value:o.value,label:o.text,unidad:o.dataset.unidad}));
        return map;
    }

    function getVal(id){ const c = migracionesChoices[id]; if(c) return (c.getValue(true) || ''); const el=document.getElementById(id); return el?el.value:''; }
    function setVal(id,v){ const c=migracionesChoices[id]; if(c) c.setChoiceByValue(String(v)); else {const el=document.getElementById(id); if(el) el.value=v;} }

    function filterSelect(targetId, fn){
        const c = migracionesChoices[targetId]; if(!c) return;
        const orig = origOptions[targetId];
        if(!orig) return;
        const cur = c.getValue(true);
        const filtered = orig.filter(o=> o.value==='' || o.value==='*' || fn(o));
        // keep * option always
        c.clearChoices();
        c.setChoices(filtered,'value','label',true);
        if(cur && filtered.some(f=>f.value==cur)) c.setChoiceByValue(String(cur));
    }

    let isSync=false;
    // Step1 cascade
    function syncOrig(changed){
        if(isSync) return; isSync=true;
        const rs = getVal('mig-orig-rs');
        const seg = getVal('mig-orig-seg');
        const place = getVal('mig-orig-place');
        const uo = getVal('mig-orig-uo');
        // Segmento filtered by RS
        filterSelect('mig-orig-seg', o=> !rs || o.rs==rs);
        // Place filtered by RS+Seg
        filterSelect('mig-orig-place', o=> {
            if(rs && o.rs && o.rs!=rs) return false;
            if(seg && o.seg && o.seg!=seg) return false;
            // if no seg, allow places without seg? filter by rs only
            if(!seg && rs) return !o.seg || o.rs==rs;
            return true;
        });
        // UO filtered by Place
        const origUoEl = document.getElementById('mig-orig-uo');
        // Rebuild UO options from original list filtered by place
        const allUoOrig = origOptions['mig-dest-uo-orig'] || [];
        // But we need to filter displayed choices: keep *
        const cUo = migracionesChoices['mig-orig-uo'];
        if(cUo){
            const filteredUo = allUoOrig.filter(o=> o.value=='' || o.value=='*' || !place || o.place==place);
            const curUo = cUo.getValue(true);
            cUo.clearChoices(); cUo.setChoices(filteredUo,'value','label',true);
            if(curUo && filteredUo.some(f=>f.value==curUo)) cUo.setChoiceByValue(String(curUo));
        }
        // Grupo filtered by UO
        const allGrupoOrig = origOptions['mig-dest-grupo-orig'] || [];
        const cG = migracionesChoices['mig-orig-grupo'];
        if(cG){
            const curG = cG.getValue(true);
            const filteredG = allGrupoOrig.filter(o=> o.value=='' || o.value=='*' || !getVal('mig-orig-uo') || getVal('mig-orig-uo')=='*' || o.unidad==getVal('mig-orig-uo'));
            cG.clearChoices(); cG.setChoices(filteredG,'value','label',true);
            if(curG && filteredG.some(f=>f.value==curG)) cG.setChoiceByValue(String(curG));
        }

        // Handle * logic: if UO=* then Grupo disabled and forced to *
        const uoVal = getVal('mig-orig-uo');
        const grupoEl = document.getElementById('mig-orig-grupo');
        if(uoVal==='*'){
            setVal('mig-orig-grupo','*');
            if(grupoEl) grupoEl.disabled=true;
            if(cG) cG.disable();
        } else {
            if(grupoEl) grupoEl.disabled=false;
            if(cG) cG.enable();
        }

        validateOrig();
        updateOrigResumen();
        isSync=false;
    }

    function syncDest(changed){
        if(isSync) return; isSync=true;
        const rs = getVal('mig-dest-rs');
        const seg = getVal('mig-dest-seg');
        const place = getVal('mig-dest-place');
        // Seg filtered by RS
        filterSelect('mig-dest-seg', o=> !rs || o.rs==rs);
        filterSelect('mig-dest-place', o=> {
            if(rs && o.rs && o.rs!=rs) return false;
            if(seg && o.seg && o.seg!=seg) return false;
            if(!seg && rs) return !o.seg || o.rs==rs;
            return true;
        });
        // Rebuild dest UO/ Grupo from live data (reload from server? we use orig data + dynamically added)
        refreshDestUoGrupo();
        isSync=false;
        validateDest();
        updateDestResumen();
        applyDestinoBloqueo();
    }

    async function refreshDestUoGrupo(){
        const place = getVal('mig-dest-place');
        const cUo = migracionesChoices['mig-dest-uo'];
        const cG = migracionesChoices['mig-dest-grupo'];
        if(!place){
            if(cUo){ cUo.clearChoices(); cUo.setChoices([{value:'',label:'Seleccione...'}],'value','label',true); }
            if(cG){ cG.clearChoices(); cG.setChoices([{value:'',label:'Seleccione...'}],'value','label',true); }
            return;
        }
        // Try to fetch via API? fallback to filtering original lists
        // We have original departments/grupos lists, filter by place
        const allUoOrig = origOptions['mig-dest-uo-orig'] || [];
        const filteredUo = allUoOrig.filter(o=> o.value=='' || !place || o.place==place);
        // But also need dynamically created ones added to origOptions
        if(cUo){
            const cur = cUo.getValue(true);
            cUo.clearChoices();
            cUo.setChoices(filteredUo,'value','label',true);
            if(cur && filteredUo.some(f=>f.value==cur)) cUo.setChoiceByValue(String(cur));
        }
        // Grupo
        const allGrupoOrig = origOptions['mig-dest-grupo-orig'] || [];
        const curUo = getVal('mig-dest-uo');
        const filteredG = allGrupoOrig.filter(o=> o.value=='' || !curUo || curUo=='*' || o.unidad==curUo);
        // Further filter by place indirectly via unidad's place, but unidades already filtered
        if(cG){
            const curG = cG.getValue(true);
            cG.clearChoices();
            cG.setChoices(filteredG,'value','label',true);
            if(curG && filteredG.some(f=>f.value==curG)) cG.setChoiceByValue(String(curG));
        }
    }

    function validateOrig(){
        const rs=getVal('mig-orig-rs'), seg=getVal('mig-orig-seg'), place=getVal('mig-orig-place');
        const uo=getVal('mig-orig-uo'), grupo=getVal('mig-orig-grupo');
        let valid = rs && seg && place;
        if(!valid){ btnADestino.disabled=true; return; }
        // If UO=* then grupo must be * (already forced)
        // If UO specific and grupo empty -> still need grupo selection? Allow either * or specific
        // Require at least UO selection (can be * )
        if(!uo){ valid=false; }
        else if(uo!=='*' && !grupo){ valid=false; }
        btnADestino.disabled = !valid;
    }
    function validateDest(){
        const rs=getVal('mig-dest-rs'), seg=getVal('mig-dest-seg'), place=getVal('mig-dest-place');
        const uo=getVal('mig-dest-uo'), grupo=getVal('mig-dest-grupo');
        const nivelOrig = getNivelOrig();
        let valid = rs && seg && place;
        if(!valid){ btnPreview.disabled=true; return; }
        if(nivelOrig==='partida'){
            if(!uo || uo==='*' || !grupo || grupo==='*') valid=false;
        } else if(nivelOrig==='area'){
            if(!uo || uo==='*') valid=false;
            // grupo debe estar vacío/bloqueado
        } else if(nivelOrig==='complejo'){
            if(uo && uo!=='*') valid=false; // debe ser * / vacío
            if(grupo && grupo!=='*') valid=false;
        }
        btnPreview.disabled = !valid;
    }
    function getNivelOrig(){
        const uo=getVal('mig-orig-uo'), grupo=getVal('mig-orig-grupo');
        if(uo==='*') return 'complejo';
        if(grupo==='*') return 'area';
        return 'partida';
    }
    function applyDestinoBloqueo(){
        const nivel = getNivelOrig();
        const cUo = migracionesChoices['mig-dest-uo']; const cG=migracionesChoices['mig-dest-grupo'];
        const elUo=document.getElementById('mig-dest-uo'); const elG=document.getElementById('mig-dest-grupo');
        const btnUo=document.getElementById('btn-crear-uo-dest'); const btnG=document.getElementById('btn-crear-grupo-dest');
        if(nivel==='complejo'){
            if(cUo){ cUo.clearChoices(); cUo.setChoices([{value:'',label:'(Bloqueado - se crearán todas)'}],'value','label',true); cUo.disable(); }
            if(elUo) elUo.disabled=true;
            if(cG){ cG.clearChoices(); cG.setChoices([{value:'',label:'(Bloqueado - se crearán todas)'}],'value','label',true); cG.disable(); }
            if(elG) elG.disabled=true;
            if(btnUo) btnUo.style.display='none';
            if(btnG) btnG.style.display='none';
        } else if(nivel==='area'){
            if(cUo) cUo.enable(); if(elUo) elUo.disabled=false; if(btnUo) btnUo.style.display='';
            if(cG){ cG.clearChoices(); cG.setChoices([{value:'',label:'(Bloqueado - se crearán partidas)'}],'value','label',true); cG.disable(); }
            if(elG) elG.disabled=true;
            if(btnG) btnG.style.display='none';
        } else {
            if(cUo) cUo.enable(); if(elUo) elUo.disabled=false; if(btnUo) btnUo.style.display='';
            if(cG) cG.enable(); if(elG) elG.disabled=false; if(btnG) btnG.style.display='';
        }
    }
    function updateOrigResumen(){
        const el=document.getElementById('mig-orig-resumen');
        if(!el) return;
        const nivel=getNivelOrig();
        const rsEl=document.getElementById('mig-orig-rs'); const segEl=document.getElementById('mig-orig-seg'); const placeEl=document.getElementById('mig-orig-place');
        const rsTxt = rsEl.options[rsEl.selectedIndex]?.text || ''; const segTxt=segEl.options[segEl.selectedIndex]?.text || ''; const placeTxt=placeEl.options[placeEl.selectedIndex]?.text || '';
        const uoTxt=document.getElementById('mig-orig-uo').options[document.getElementById('mig-orig-uo').selectedIndex]?.text || '';
        const gTxt=document.getElementById('mig-orig-grupo').options[document.getElementById('mig-orig-grupo').selectedIndex]?.text || '';
        if(nivel==='partida') el.innerText = `Origen: Partida "${gTxt}" en ${uoTxt} / ${placeTxt}`;
        else if(nivel==='area') el.innerText = `Origen: Todas las partidas de ${uoTxt} en ${placeTxt}`;
        else el.innerText = `Origen: Todas las áreas y partidas de ${placeTxt}`;
    }
    function updateDestResumen(){
        const el=document.getElementById('mig-dest-resumen');
        if(!el) return;
        const nivel=getNivelOrig();
        const placeTxt=document.getElementById('mig-dest-place').options[document.getElementById('mig-dest-place').selectedIndex]?.text || '';
        const uoTxt=document.getElementById('mig-dest-uo').options[document.getElementById('mig-dest-uo').selectedIndex]?.text || '';
        const gTxt=document.getElementById('mig-dest-grupo').options[document.getElementById('mig-dest-grupo').selectedIndex]?.text || '';
        if(nivel==='partida') el.innerText = `Destino: Partida "${gTxt}"`;
        else if(nivel==='area') el.innerText = `Destino: Área "${uoTxt}"`;
        else el.innerText = `Destino: Complejo "${placeTxt}"`;
    }

    // Listeners orig
    ['mig-orig-rs','mig-orig-seg','mig-orig-place','mig-orig-uo','mig-orig-grupo'].forEach(id=>{
        const el=document.getElementById(id); if(el) el.addEventListener('change', ()=> syncOrig(id));
    });
    ['mig-dest-rs','mig-dest-seg','mig-dest-place','mig-dest-uo','mig-dest-grupo'].forEach(id=>{
        const el=document.getElementById(id); if(el) el.addEventListener('change', ()=> syncDest(id));
    });

    // Buttons
    btnMigrar.onclick = ()=>{
        pantallaLista.classList.add('hidden'); pantallaForm.classList.add('hidden'); pantallaMigrar.classList.remove('hidden');
        // Reset step
        step1.classList.remove('hidden'); step2.classList.add('hidden'); previewBox.classList.add('hidden');
        badge.innerText='Paso 1: Origen'; badge.className='px-3 py-1 bg-indigo-100 text-indigo-700 rounded-full text-xs font-bold';
        // Reset values
        ['mig-orig-rs','mig-orig-seg','mig-orig-place','mig-orig-uo','mig-orig-grupo','mig-dest-rs','mig-dest-seg','mig-dest-place','mig-dest-uo','mig-dest-grupo'].forEach(id=> setVal(id,''));
        // Rebuild filters
        syncOrig(); syncDest(); validateOrig();
    };
    btnRegresar.onclick = ()=>{
        pantallaMigrar.classList.add('hidden'); pantallaLista.classList.remove('hidden');
    };
    btnADestino.onclick = ()=>{
        step1.classList.add('hidden'); step2.classList.remove('hidden'); badge.innerText='Paso 2: Destino'; badge.className='px-3 py-1 bg-emerald-100 text-emerald-700 rounded-full text-xs font-bold';
        previewBox.classList.add('hidden'); syncDest(); validateDest(); applyDestinoBloqueo();
    };
    btnVolverOrigen.onclick = ()=>{
        step2.classList.add('hidden'); step1.classList.remove('hidden'); badge.innerText='Paso 1: Origen'; badge.className='px-3 py-1 bg-indigo-100 text-indigo-700 rounded-full text-xs font-bold';
        previewBox.classList.add('hidden');
    };

    // Facilitadores crear
    document.getElementById('btn-crear-place-dest')?.addEventListener('click', async ()=>{
        const rs=getVal('mig-dest-rs'); const seg=getVal('mig-dest-seg');
        if(!rs||!seg){ mostrarNotificacion('Seleccione RS y Segmento primero','warning'); return; }
        const nombre = prompt('Nombre corto del nuevo complejo:');
        if(!nombre) return;
        const nombreCompleto = prompt('Nombre completo (opcional):') || nombre;
        const fd=new FormData(); fd.append('Nombre_Corto',nombre); fd.append('Nombre_Completo',nombreCompleto); fd.append('ID_RazonSocial',rs); fd.append('id_segmento',seg);
        try{
            const res=await SendDataEnd('modales/crud_places/insertar',{method:'POST',body:fd});
            if(res.success){ mostrarNotificacion('Complejo creado','success');
                // Add to options
                const opt={value: res.id || '?', label:nombre, rs:rs, seg:seg};
                // we don't have ID, reload? Try to fetch places
                location.reload(); // simple
            } else mostrarNotificacion(res.message||'Error','error');
        }catch(e){ mostrarNotificacion('Error','error');}
    });
    document.getElementById('btn-crear-uo-dest')?.addEventListener('click', async ()=>{
        const place=getVal('mig-dest-place');
        if(!place){ mostrarNotificacion('Seleccione complejo primero','warning'); return; }
        const nombre = prompt('Nombre de la nueva área de operación:');
        if(!nombre) return;
        const fd=new FormData(); fd.append('Nombre',nombre); fd.append('ID_Place',place);
        try{
            const res=await SendDataEnd('modales/crud_unidades_operativas/insertar',{method:'POST',body:fd});
            if(res.success){ mostrarNotificacion('Área creada','success'); location.reload(); }
            else mostrarNotificacion(res.message||'Error','error');
        }catch(e){ mostrarNotificacion('Error','error');}
    });
    document.getElementById('btn-crear-grupo-dest')?.addEventListener('click', async ()=>{
        const uo=getVal('mig-dest-uo') || getVal('mig-dest-place');
        // Need UO id
        const idUo=getVal('mig-dest-uo');
        if(!idUo || idUo==='*'){ mostrarNotificacion('Seleccione área primero','warning'); return; }
        const nombre = prompt('Nombre de la nueva partida:');
        if(!nombre) return;
        const fd=new FormData(); fd.append('Nombre',nombre); fd.append('Descripcion',nombre); fd.append('ID_UnidadOperativa',idUo);
        try{
            const res=await SendDataEnd('modales/crud_grupos_presupuestales/insertar',{method:'POST',body:fd});
            if(res.success){ mostrarNotificacion('Partida creada','success'); location.reload(); }
            else mostrarNotificacion(res.message||'Error','error');
        }catch(e){ mostrarNotificacion('Error','error');}
    });

    // Preview
    btnPreview.onclick = async ()=>{
        const origen={ rs:getVal('mig-orig-rs'), seg:getVal('mig-orig-seg'), place:getVal('mig-orig-place'), unidad:getVal('mig-orig-uo'), grupo:getVal('mig-orig-grupo') };
        const destino={ rs:getVal('mig-dest-rs'), seg:getVal('mig-dest-seg'), place:getVal('mig-dest-place'), unidad:getVal('mig-dest-uo'), grupo:getVal('mig-dest-grupo') };
        // Normalize empty to null
        if(origen.unidad==='') origen.unidad=null; if(origen.grupo==='') origen.grupo=null;
        if(destino.unidad==='') destino.unidad=null; if(destino.grupo==='') destino.grupo=null;
        // For destino when nivel area/complejo, set unidad/grupo to * or null accordingly
        const nivel=getNivelOrig();
        if(nivel==='area'){ destino.grupo='*'; }
        if(nivel==='complejo'){ destino.unidad='*'; destino.grupo='*'; }

        btnPreview.disabled=true; btnPreview.innerText='Cargando...';
        try{
            const res=await SendDataEnd('api/migraciones/preview',{method:'POST', body: {origen,destino}});
            if(res.success){
                const pv=res.preview;
                migracionPreviewHash = pv.preview_hash;
                renderPreview(pv);
                previewBox.classList.remove('hidden');
                btnEjecutar.disabled = (pv.total_a_insertar===0);
                if(pv.total_a_insertar===0) mostrarNotificacion('Nada que migrar (todo duplicado)','warning');
            } else {
                mostrarNotificacion(res.message||res.messages?.error||'Error','error');
            }
        }catch(e){
            const msg = e.data?.messages?.error || e.data?.message || e.message || 'Error';
            mostrarNotificacion(msg,'error');
            console.error(e);
        } finally { btnPreview.disabled=false; btnPreview.innerText='Previsualizar'; }
    };

    function renderPreview(pv){
        const cont=document.getElementById('migrar-preview-content');
        if(!cont) return;
        let html=`<div class="mb-2 font-bold">Nivel: ${pv.nivel} | Total origen: ${pv.total_origen} | A insertar: <span class="text-green-700">${pv.total_a_insertar}</span></div>`;
        pv.escenarios.forEach(esc=>{
            if(esc.tipo==='partida'){
                html+=`<div class="border p-2 rounded mb-2 bg-white"><div>Partida origen ${esc.origen_grupo} -> destino ${esc.destino_grupo}</div><div>Origen: ${esc.origen_count} | Destino existentes: ${esc.destino_count} | A insertar: <b>${esc.a_insertar}</b> | Duplicados: ${esc.duplicados_ignorados}</div>${esc.ejemplos.length?`<div class="text-[11px] text-gray-500">Ej: ${esc.ejemplos.join(', ')}</div>`:''}</div>`;
            } else if(esc.tipo==='area'){
                html+=`<div class="border p-2 rounded mb-2 bg-white"><div>Área ${esc.origen_unidad} -> ${esc.destino_unidad}</div><div>Grupos origen: ${esc.grupos_origen} | A crear: ${esc.grupos_a_crear} | Productos a insertar: <b>${esc.total_a_insertar}</b></div><div class="mt-1">`;
                esc.detalles.forEach(d=>{ html+=`<div class="text-[11px]">- ${d.partida_origen} : ${d.productos_origen} -> ${d.productos_a_insertar} ${d.existe_en_destino?'':' (nueva partida)'}</div>`; });
                html+=`</div></div>`;
            } else if(esc.tipo==='complejo'){
                html+=`<div class="border p-2 rounded mb-2 bg-white"><div>Complejo ${esc.origen_place} -> ${esc.destino_place}</div><div>Áreas origen: ${esc.unidades_origen} | A crear: ${esc.unidades_a_crear} | Grupos a crear: ${esc.grupos_a_crear} | Productos a insertar: <b>${esc.total_a_insertar}</b></div><div class="mt-1">`;
                esc.detalles.forEach(d=>{ html+=`<div class="text-[11px]">- ${d.area_origen} (${d.grupos_en_area} partidas) ${d.existe_en_destino?'':' (nueva área)'}</div>`; });
                html+=`</div></div>`;
            }
        });
        cont.innerHTML=html;
    }

    btnEjecutar.onclick = async ()=>{
        if(!migracionPreviewHash){ mostrarNotificacion('Haga preview primero','warning'); return; }
        if(!confirm('¿Ejecutar migración? Se crearán los registros faltantes.')) return;
        const origen={ rs:getVal('mig-orig-rs'), seg:getVal('mig-orig-seg'), place:getVal('mig-orig-place'), unidad:getVal('mig-orig-uo'), grupo:getVal('mig-orig-grupo') };
        const destino={ rs:getVal('mig-dest-rs'), seg:getVal('mig-dest-seg'), place:getVal('mig-dest-place'), unidad:getVal('mig-dest-uo'), grupo:getVal('mig-dest-grupo') };
        const nivel=getNivelOrig();
        if(nivel==='area'){ destino.grupo='*'; }
        if(nivel==='complejo'){ destino.unidad='*'; destino.grupo='*'; }
        btnEjecutar.disabled=true; btnEjecutar.innerText='Ejecutando...';
        try{
            const res=await SendDataEnd('api/migraciones/ejecutar',{method:'POST', body: {origen,destino, preview_hash: migracionPreviewHash}});
            if(res.success){
                document.getElementById('migrar-result').innerHTML=`<div class="p-3 bg-green-50 border border-green-300 rounded text-green-800">✅ ${res.message} <br>Insertados: ${res.resultado.insertados} | Grupos creados: ${res.resultado.grupos_creados} | Áreas creadas: ${res.resultado.unidades_creadas} | Duplicados ignorados: ${res.resultado.duplicados_ignorados}</div>`;
                mostrarNotificacion('Migración completada','success');
                // Reload catalog next time
            } else mostrarNotificacion(res.message||res.messages?.error||'Error','error');
        }catch(e){
            const msg = e.data?.messages?.error || e.data?.message || e.message || 'Error al ejecutar';
            mostrarNotificacion(msg,'error'); console.error(e);
            document.getElementById('migrar-result').innerHTML=`<div class="p-3 bg-red-50 border border-red-300 rounded text-red-800">❌ ${msg}</div>`;
        }
        finally{ btnEjecutar.disabled=false; btnEjecutar.innerText='Ejecutar migración'; }
    };
}

// Expose for mbscript init
window.initMigracionesCatalogo = initMigracionesCatalogo;
