// External library dependencies.
import React from 'react';
import metadata from './module.json'; 

// --- ROBUST GLOBAL DESTRUCTURING ---
// We use 'window' safely here to prevent linter errors if window is undefined in the editor environment
const w = typeof window !== 'undefined' ? window : {};
const DiviModule = w.divi?.module || {};

const {
  ModuleContainer,
  StyleContainer,
  elementClassnames,
} = DiviModule;

// --- 1. Style Component ---
const ModuleStyles = ({ attrs, elements, settings, noStyleTag }) => (
  <StyleContainer noStyleTag={noStyleTag}>
    {elements.style({ attrName: 'module' })}
  </StyleContainer>
);

/**
 * Function for registering module classnames.
 */
const moduleClassnames = ({ classnamesInstance, attrs }) => {
  classnamesInstance.add(elementClassnames({ attrs: attrs?.module?.decoration ?? {} }));
}

/**
 * Noka Gallery Module Object (Stateless)
 */
const NokaGalleryModule = {
  metadata, // Base metadata (will be overridden during registration)

  renderers: {
    edit: ({ attrs, id, name, elements }) => {
      const galleryId = attrs?.gallery_select?.innerContent?.desktop?.value; 
      
      // Check if Dynamic Content is being used (starts with @)
      const isDynamic = galleryId && typeof galleryId === 'string' && galleryId.startsWith('@');

      let previewContent;
      
      if (!galleryId || galleryId === 'none') {
        previewContent = (
            <div className="noka-placeholder-vb" style={{padding:'20px', textAlign:'center', border:'1px dashed #DDD', background: '#f9f9f9', color: '#333'}}>
                <strong>Noka Gallery</strong><br/>
                Please select a Gallery in the settings.
            </div>
        );
      } else {
        const displayText = isDynamic ? "Dynamic Gallery Selected" : `Gallery ID: ${galleryId}`;
        previewContent = (
            <div className="noka-loading-vb" style={{padding:'20px', textAlign:'center', border:'1px dashed #7AA', minHeight:'100px', background: '#f0f8ff', color: '#333'}}>
                <strong>{displayText}</strong><br/>
                <small>(Gallery will render on the frontend)</small>
            </div>
        );
      }

      return (
        <ModuleContainer
          attrs={attrs}
          elements={elements}
          id={id}
          moduleClassName="noka_gallery_module"
          name={name}
          stylesComponent={ModuleStyles}
          classnamesFunction={moduleClassnames}
        >
          {elements.styleComponents({ attrName: 'module' })}
          <div className="et_pb_module_inner">
            {previewContent}
          </div>
        </ModuleContainer>
      );
    },
  },
  
  placeholderContent: {
    module: {
      decoration: {
        background: {
          desktop: { value: { color: '#ffffff' } }
        }
      }
    },
    gallery_select: {
      innerContent: {
        desktop: { value: 'none' }
      }
    }
  },
};

// --- SAFER MODULE REGISTRATION ---

const hooks = w.vendor?.wp?.hooks;
const diviLib = w.divi?.moduleLibrary;

if (hooks && diviLib) {
    hooks.addAction('divi.moduleLibrary.registerModuleLibraryStore.after', 'noka.galleryModule', () => {
        
        // 1. Deep clone metadata to avoid "Read Only" errors in strict mode
        const dynamicMetadata = JSON.parse(JSON.stringify(metadata));

        // 2. Inject the Dropdown Options from the Global Variable
        // We use a default object if NokaData is missing to prevent crashes
        const galleryOptions = w.NokaData || { 'none': 'No Galleries Found (Check PHP)' };

        try {
            // Safely navigate to the dropdown component settings
            const settings = dynamicMetadata?.attributes?.gallery_select?.settings?.innerContent?.item;
            
            if (settings && settings.component) {
                // Inject the options prop
                settings.component.props = {
                    ...settings.component.props,
                    options: galleryOptions
                };
            }
        } catch (err) {
            console.error('Noka Gallery: Failed to inject options', err);
        }

        // 3. Register the modified metadata
        diviLib.registerModule(dynamicMetadata, NokaGalleryModule);
        console.log('Noka Gallery: Registered successfully with dynamic options.');
    });
} else {
    console.warn('Noka Gallery: Divi or WP Hooks not found. Module not registered.');
}



export default NokaGalleryModule;