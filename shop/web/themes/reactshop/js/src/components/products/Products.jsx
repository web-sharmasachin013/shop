import React from "react";
import Product from "../products/Product";

function Products(props) {
  const { products } = props;
  return (
    <div className="px-lg-5 text-dark">
      <div className="row row-cols-1 row-col-sm-2 row-cols-lg-3 row-cols-xl-4 gy-4 justify-content-start">
        {products.map((product) => {
          return <Product key={product.id} product={product}></Product>;
        })}
      </div>
    </div>
  );
}

export default Products;
