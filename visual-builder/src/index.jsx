// External library dependencies.
import React from 'react';
import metadata from './module.json'; 

// CRITICAL: Ensure you import your styles if they are bundled via Webpack
// Uncomment this line if your CSS source file exists here:
// import '../../includes/assets/css/style.css'; 

// --- ROBUST GLOBAL DESTRUCTURING ---
// Safely assign global objects to variables.
const DiviModule = window?.divi?.module || {};

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
  // FIX: elementClassnames is now safely available from the global destructuring
  classnamesInstance.add(elementClassnames({ attrs: attrs?.module?.decoration ?? {} }));
}

/**
 * Noka Gallery Module Object (Stateless, Tutorial Style).
 * This component handles the Visual Builder preview.
 */
const NokaGalleryModule = {
  // Metadata that is used on Visual Builder and Frontend
  metadata,

  // Layout renderer components.
  renderers: {
    // This is the component that runs in the Visual Builder iframe
    edit: ({ attrs, id, name, elements }) => {
      // Logic for Visual Builder Preview (simplified)
      const galleryId = attrs?.gallery_select?.innerContent?.desktop?.value; 
      let previewContent;
      
      if (!galleryId || galleryId === 'none') {
        previewContent = <div className="noka-placeholder-vb" style={{padding:'20px', textAlign:'center', border:'1px dashed #DDD'}}>Select a Noka Gallery in the settings sidebar.</div>;
      } else {
        previewContent = <div className="noka-loading-vb" style={{padding:'20px', textAlign:'center', border:'1px dashed #7AA', minHeight:'100px'}}>Gallery ID {galleryId} Selected (Front-End Rendered by PHP Shortcode).</div>;
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

  // Placeholder content for new modules
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

// --- MODULE REGISTRATION: Clean, Immediate Check (The Divi 5 Way) ---

// Get the registration function, which is made available by 'divi-module-library' dependency.
const registerModuleFunc = window.divi?.moduleLibrary?.registerModule || window.divi?.registry?.registerModule;

if (registerModuleFunc) {
    // Register immediately once the script is executed.
    registerModuleFunc(NokaGalleryModule.metadata, NokaGalleryModule);
    console.log('SUCCESS: Noka Gallery Registered (Clean Check)!');
} else {
    // This should only happen if the PHP dependency logic is wrong.
    console.error('ERROR: Divi registration object not found. Module registration failed.');
}

// Ensure the source file is built after this change.

export default NokaGalleryModule;