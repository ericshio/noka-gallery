import React from 'react';
import metadata from './module.json'; 

const w = typeof window !== 'undefined' ? window : {};

// Helpers
const getAttributeValue = (attr) => {
    if (!attr) return null;
    if (attr?.innerContent?.desktop?.value !== undefined) return attr.innerContent.desktop.value;
    if (attr?.desktop?.value !== undefined) return attr.desktop.value;
    if (attr?.value !== undefined) return attr.value;
    if (typeof attr === 'string' || typeof attr === 'number') return attr;
    return null;
};

const formatOptions = (data) => {
    if (!data) return [{ value: 'none', label: 'No Galleries Found' }];
    if (Array.isArray(data)) return data;
    return Object.entries(data).map(([val, label]) => ({ value: String(val), label: String(label) }));
};

const registerNoka = () => {
    try {
        const diviLib = w.divi?.moduleLibrary || w.divi?.registry;
        if (!diviLib?.registerModule) return false;

        const dynamicMetadata = JSON.parse(JSON.stringify(metadata));
        const rawData = w.NokaData || { 'none': 'Select a Gallery...' };
        
        try {
            const settings = dynamicMetadata?.attributes?.gallery_select?.settings?.innerContent?.item;
            if (settings?.component) {
                settings.component.props = { 
                    ...settings.component.props, 
                    options: formatOptions(rawData) 
                };
            }
        } catch (e) {}

        const NokaGalleryModule = {
            metadata: dynamicMetadata,
            renderers: {
                edit: ({ attrs, id, name, elements }) => {
                    const diviModule = w.divi?.module || {};
                    const ModuleContainer = diviModule.ModuleContainer || 'div';
                    const StyleContainer = diviModule.StyleContainer || (({children}) => <>{children}</>);
                    const elementClassnames = diviModule.elementClassnames;

                    const stylesComponent = ({ elements, noStyleTag }) => (
                        <StyleContainer noStyleTag={noStyleTag}>{elements.style({ attrName: 'module' })}</StyleContainer>
                    );
                    const classnamesFunc = ({ classnamesInstance, attrs }) => {
                        if(elementClassnames) classnamesInstance.add(elementClassnames({ attrs: attrs?.module?.decoration ?? {} }));
                    };

                    const galleryId = getAttributeValue(attrs?.gallery_select);
                    const hasValue = galleryId && galleryId !== 'none' && galleryId !== '0';

                    return (
                        <ModuleContainer attrs={attrs} elements={elements} id={id} moduleClassName="noka_gallery_module" name={name} stylesComponent={stylesComponent} classnamesFunction={classnamesFunc}>
                            {elements.styleComponents({ attrName: 'module' })}
                            <div className="et_pb_module_inner">
                                <div style={{background:'#f4f4f4', padding:'20px', textAlign:'center', border:'1px dashed #ccc', color:'#333'}}>
                                    <strong style={{display:'block', marginBottom:'5px'}}>Noka Gallery</strong>
                                    { hasValue
                                        ? <span style={{color:'#0085ba', fontWeight:'bold'}}>Gallery ID: {galleryId}</span> 
                                        : <span style={{color:'#d63638'}}>Please select a gallery in the settings.</span> 
                                    }
                                </div>
                            </div>
                        </ModuleContainer>
                    );
                },
            },
            placeholderContent: {
                module: { decoration: { background: { desktop: { value: { color: '#ffffff' } } } } },
                gallery_select: { innerContent: { desktop: { value: 'none' } } }
            },
        };

        diviLib.registerModule(dynamicMetadata, NokaGalleryModule);
        console.log('NOKA: Registered Successfully.');
        return true;

    } catch (err) { console.error('NOKA Registration Error', err); return true; }
};

// --- FIX: DELAY START ---
// Wait 1.5s for Divi's internal stores (divi/settings) to be ready.
setTimeout(() => {
    const checkDivi = setInterval(() => {
        if (w.React && (w.divi?.moduleLibrary || w.divi?.registry)) {
            if (registerNoka()) clearInterval(checkDivi);
        }
    }, 250);

    setTimeout(() => clearInterval(checkDivi), 15000);
}, 1500);

export default {};